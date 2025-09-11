import express from 'express';
import cors from 'cors';
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

export default router;


