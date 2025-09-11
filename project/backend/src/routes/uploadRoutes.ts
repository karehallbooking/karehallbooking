import express from 'express';
import cors from 'cors';
import multer from 'multer';
import { MongoClient, ObjectId, GridFSBucket, ServerApiVersion } from 'mongodb';
import crypto from 'crypto';

const router = express.Router();

// POST /api/uploads/signature
// Returns a signed payload for Cloudinary raw uploads with access_mode=public
// Allow CORS explicitly for this endpoint (frontend may run from multiple origins)
router.options('/signature', cors());
router.post('/signature', cors(), async (req, res) => {
  try {
    const cloudName = process.env.CLOUDINARY_CLOUD_NAME;
    const apiKey = process.env.CLOUDINARY_API_KEY;
    const apiSecret = process.env.CLOUDINARY_API_SECRET;

    if (!cloudName || !apiKey || !apiSecret) {
      return res.status(500).json({
        success: false,
        message: 'Cloudinary environment variables are not configured',
        error: 'CLOUDINARY_CONFIG_MISSING'
      });
    }

    // simple health probe when requested: { "health": true }
    if (req.query.health === 'true') {
      return res.json({
        success: true,
        message: 'Cloudinary configuration present',
        cloud_name: cloudName,
        api_key: apiKey
      });
    }

    const timestamp = Math.floor(Date.now() / 1000);
    const paramsToSign = `access_mode=public&timestamp=${timestamp}`;
    const signature = crypto
      .createHash('sha1')
      .update(paramsToSign + apiSecret)
      .digest('hex');

    // Explicit CORS headers (defensive in case upstream proxy strips them)
    res.set('Access-Control-Allow-Origin', '*');
    res.set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    res.set('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');

    return res.json({
      success: true,
      timestamp,
      signature,
      access_mode: 'public',
      cloud_name: cloudName,
      api_key: apiKey,
      upload_url: `https://api.cloudinary.com/v1_1/${cloudName}/raw/upload`
    });
  } catch (err: any) {
    return res.status(500).json({
      success: false,
      message: err?.message || 'Failed to generate signature',
      error: 'SIGNATURE_ERROR'
    });
  }
});

// ===== MongoDB GridFS setup =====
const MONGODB_URI = process.env.MONGODB_URI || 'mongodb+srv://kotarisandeep198_db_user:e5upm3bxvSMcQGwr@sandeephallbooking.dk27kdn.mongodb.net/?retryWrites=true&w=majority&appName=SandeepHallbooking';
const MONGODB_DB_NAME = process.env.MONGODB_DB_NAME || 'karehallbooking';

let client: MongoClient | null = null;
let bucket: GridFSBucket | null = null;

async function ensureMongoConnected(): Promise<void> {
  try {
    if (client) {
      await client.db('admin').command({ ping: 1 });
      return;
    }
  } catch {
    try { await client?.close(); } catch { /* noop */ }
    client = null;
  }

  client = new MongoClient(MONGODB_URI, {
    maxPoolSize: 10,
    connectTimeoutMS: 15000,
    socketTimeoutMS: 45000,
    retryWrites: true,
    serverApi: { version: ServerApiVersion.v1, strict: false, deprecationErrors: true }
  });
  await client.connect();
  await client.db('admin').command({ ping: 1 });
}

async function getBucket(): Promise<GridFSBucket> {
  await ensureMongoConnected();
  if (!bucket) {
    const db = (client as MongoClient).db(MONGODB_DB_NAME);
    bucket = new GridFSBucket(db, { bucketName: 'pdfs' });
  }
  return bucket as GridFSBucket;
}

// Multer memory storage (we stream into GridFS)
const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 20 * 1024 * 1024 }, // 20MB
  fileFilter: (_req: express.Request, file: Express.Multer.File, cb: multer.FileFilterCallback) => {
    if (file.mimetype !== 'application/pdf') return cb(new Error('Only PDF files are allowed'));
    cb(null, true);
  }
});

// POST /api/uploads/pdf → store PDF in GridFS
type MulterRequest = express.Request & { file?: Express.Multer.File };

router.post('/pdf', cors(), upload.single('file'), async (req: MulterRequest, res: express.Response): Promise<void> => {
  try {
    if (!req.file) {
      res.status(400).json({ success: false, message: 'No file uploaded', error: 'NO_FILE' });
      return;
    }
    let b: GridFSBucket;
    try {
      b = await getBucket();
    } catch (e: any) {
      // Attempt one reconnect if topology was closed
      await ensureMongoConnected();
      b = await getBucket();
    }
    const filename = req.file.originalname || 'document.pdf';
    const uploadStream = b.openUploadStream(filename, { contentType: 'application/pdf' });
    uploadStream.end(req.file.buffer);
    uploadStream.on('error', (err: Error) => {
      res.status(500).json({ success: false, message: err.message, error: 'UPLOAD_ERROR' });
    });
    uploadStream.on('finish', () => {
      const id = (uploadStream.id as ObjectId).toHexString();
      const host = req.get('x-forwarded-host') || req.get('host');
      const proto = (req.get('x-forwarded-proto') || req.protocol || 'https').split(',')[0];
      const absoluteUrl = host ? `${proto}://${host}/api/uploads/pdf/${id}` : `/api/uploads/pdf/${id}`;
      res.json({ success: true, fileId: id, url: absoluteUrl });
    });
  } catch (err: any) {
    res.status(500).json({ success: false, message: err?.message || 'Upload failed', error: 'UPLOAD_FAILED' });
  }
});

// GET /api/uploads/pdf/:id → stream PDF
router.get('/pdf/:id', cors(), async (req: express.Request, res: express.Response): Promise<void> => {
  try {
    const id = req.params.id;
    if (!ObjectId.isValid(id)) {
      res.status(400).json({ success: false, message: 'Invalid id', error: 'INVALID_ID' });
      return;
    }
    const b = await getBucket();
    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Accept-Ranges', 'bytes');
    const downloadStream = b.openDownloadStream(new ObjectId(id));
    downloadStream.on('error', () => res.status(404).end());
    downloadStream.pipe(res);
  } catch (err: any) {
    res.status(500).json({ success: false, message: err?.message || 'Stream failed', error: 'STREAM_FAILED' });
  }
});

// DELETE /api/uploads/pdf/:id → delete PDF
router.delete('/pdf/:id', cors(), async (req: express.Request, res: express.Response): Promise<void> => {
  try {
    const id = req.params.id;
    if (!ObjectId.isValid(id)) {
      res.status(400).json({ success: false, message: 'Invalid id', error: 'INVALID_ID' });
      return;
    }
    const b = await getBucket();
    await b.delete(new ObjectId(id));
    res.json({ success: true });
  } catch (err: any) {
    res.status(500).json({ success: false, message: err?.message || 'Delete failed', error: 'DELETE_FAILED' });
  }
});

export default router;

// Secure proxy for fetching public PDFs from Cloudinary to avoid client-side 401/CORS
router.get('/proxy', cors(), async (req: express.Request, res: express.Response): Promise<void> => {
  try {
    const target = String(req.query.url || '');
    if (!target) {
      res.status(400).json({ success: false, message: 'Missing url', error: 'MISSING_URL' });
      return;
    }
    const url = new URL(target);
    if (!/\.cloudinary\.com$/i.test(url.hostname) && !/^res\.cloudinary\.com$/i.test(url.hostname)) {
      res.status(400).json({ success: false, message: 'Only Cloudinary URLs are allowed', error: 'INVALID_HOST' });
      return;
    }

    const upstream = await fetch(target);
    if (!upstream.ok) {
      const text = await upstream.text();
      res.status(upstream.status).send(text);
      return;
    }

    const contentType = upstream.headers.get('content-type') || 'application/pdf';
    const buffer = Buffer.from(await upstream.arrayBuffer());
    res.setHeader('Content-Type', contentType);
    res.setHeader('Cache-Control', 'public, max-age=300');
    res.status(200).send(buffer);
    return;
  } catch (err: any) {
    res.status(500).json({ success: false, message: err?.message || 'Proxy failed', error: 'PROXY_ERROR' });
    return;
  }
});


