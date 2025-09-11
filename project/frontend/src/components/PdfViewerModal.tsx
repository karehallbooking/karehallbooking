import React, { useEffect, useState } from 'react';

interface PdfViewerModalProps {
  isOpen: boolean;
  onClose: () => void;
  url: string; // remote pdf url
  title?: string;
}

// Fetch pdf as blob and render via object URL inside an iframe to avoid cross-site viewer issues
export const PdfViewerModal: React.FC<PdfViewerModalProps> = ({ isOpen, onClose, url, title }) => {
  const [blobUrl, setBlobUrl] = useState<string>('');
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string>('');

  useEffect(() => {
    let revoked = false;
    async function fetchPdf() {
      if (!isOpen || !url) return;
      setLoading(true);
      setError('');
      try {
        // Try direct first (we serve PDFs from our backend GridFS)
        let res = await fetch(url);
        if (!res.ok) {
          // Fallback to proxy if the direct request is blocked by CSP/CORS
          const encoded = encodeURIComponent(url);
          const proxyUrl = `https://karehallbooking-g695.onrender.com/api/uploads/proxy?url=${encoded}`;
          res = await fetch(proxyUrl);
        }
        if (!res.ok) {
          // Fallback: load direct URL
          setBlobUrl(url);
          return;
        }
        const blob = await res.blob();
        if (revoked) return;
        const objectUrl = URL.createObjectURL(blob);
        setBlobUrl(objectUrl);
      } catch {
        // Fallback to direct URL if fetch fails (e.g., 401 due to hotlink protection/CORS)
        setBlobUrl(url);
      } finally {
        setLoading(false);
      }
    }
    fetchPdf();
    return () => {
      revoked = true;
      if (blobUrl && blobUrl.startsWith('blob:')) URL.revokeObjectURL(blobUrl);
      setBlobUrl('');
    };
  }, [isOpen, url]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/60" onClick={onClose} />
      <div className="relative bg-white rounded-lg shadow-xl w-[95vw] h-[92vh] max-w-6xl">
        <div className="flex items-center justify-between p-3 border-b">
          <h3 className="text-sm font-semibold text-gray-700 truncate">{title || 'Document'}</h3>
          <button onClick={onClose} className="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200">✕</button>
        </div>
        <div className="w-full h-[calc(92vh-40px)]">
          {loading ? (
            <div className="w-full h-full flex items-center justify-center text-gray-600">Loading PDF…</div>
          ) : (
            <iframe title="pdf" src={blobUrl || url} className="w-full h-full" />
          )}
        </div>
      </div>
    </div>
  );
};



