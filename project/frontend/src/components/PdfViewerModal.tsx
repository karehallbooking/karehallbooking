import React, { useEffect, useState, useCallback } from 'react';

interface PdfViewerModalProps {
  isOpen: boolean;
  onClose: () => void;
  url: string; // remote pdf url
  title?: string;
}

// PDF cache to store loaded PDFs
const pdfCache = new Map<string, { blobUrl: string; timestamp: number }>();
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

// Preload PDFs in the background
const preloadPdf = async (url: string): Promise<string | null> => {
  if (pdfCache.has(url)) {
    const cached = pdfCache.get(url)!;
    if (Date.now() - cached.timestamp < CACHE_DURATION) {
      return cached.blobUrl;
    }
    // Cache expired, remove it
    URL.revokeObjectURL(cached.blobUrl);
    pdfCache.delete(url);
  }

  try {
    // Try direct fetch first
    let res = await fetch(url, {
      method: 'HEAD', // Just check if file exists
      cache: 'force-cache'
    });
    
    if (!res.ok) {
      // Fallback to proxy
      const encoded = encodeURIComponent(url);
      const proxyUrl = `https://karehallbooking-g695.onrender.com/api/uploads/proxy?url=${encoded}`;
      res = await fetch(proxyUrl, { cache: 'force-cache' });
    }
    
    if (!res.ok) {
      return null;
    }

    // Now fetch the actual content
    const contentRes = await fetch(res.url, { cache: 'force-cache' });
    if (!contentRes.ok) return null;
    
    const blob = await contentRes.blob();
    const objectUrl = URL.createObjectURL(blob);
    
    // Cache the result
    pdfCache.set(url, { blobUrl: objectUrl, timestamp: Date.now() });
    
    return objectUrl;
  } catch (error) {
    console.warn('Failed to preload PDF:', error);
    return null;
  }
};

export const PdfViewerModal: React.FC<PdfViewerModalProps> = ({ isOpen, onClose, url, title }) => {
  const [blobUrl, setBlobUrl] = useState<string>('');
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string>('');
  const [progress, setProgress] = useState<number>(0);

  const loadPdf = useCallback(async () => {
    if (!isOpen || !url) return;
    
    setLoading(true);
    setError('');
    setProgress(0);

    try {
      // Check cache first
      if (pdfCache.has(url)) {
        const cached = pdfCache.get(url)!;
        if (Date.now() - cached.timestamp < CACHE_DURATION) {
          setBlobUrl(cached.blobUrl);
          setLoading(false);
          return;
        }
        // Cache expired, remove it
        URL.revokeObjectURL(cached.blobUrl);
        pdfCache.delete(url);
      }

      setProgress(20);

      // Try direct fetch first
      let res = await fetch(url, {
        cache: 'force-cache'
      });
      
      setProgress(40);
      
      if (!res.ok) {
        // Fallback to proxy
        const encoded = encodeURIComponent(url);
        const proxyUrl = `https://karehallbooking-g695.onrender.com/api/uploads/proxy?url=${encoded}`;
        res = await fetch(proxyUrl, { cache: 'force-cache' });
      }
      
      setProgress(60);
      
      if (!res.ok) {
        // Final fallback: use direct URL
        setBlobUrl(url);
        setLoading(false);
        return;
      }

      setProgress(80);

      const blob = await res.blob();
      const objectUrl = URL.createObjectURL(blob);
      
      // Cache the result
      pdfCache.set(url, { blobUrl: objectUrl, timestamp: Date.now() });
      
      setBlobUrl(objectUrl);
      setProgress(100);
    } catch (error) {
      console.error('Error loading PDF:', error);
      setError('Failed to load PDF');
      // Fallback to direct URL
      setBlobUrl(url);
    } finally {
      setLoading(false);
    }
  }, [isOpen, url]);

  useEffect(() => {
    loadPdf();
    
    return () => {
      // Don't revoke blob URL immediately as it might be cached
    };
  }, [loadPdf]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      // Clean up expired cache entries
      const now = Date.now();
      for (const [key, value] of pdfCache.entries()) {
        if (now - value.timestamp > CACHE_DURATION) {
          URL.revokeObjectURL(value.blobUrl);
          pdfCache.delete(key);
        }
      }
    };
  }, []);

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
            <div className="w-full h-full flex flex-col items-center justify-center text-gray-600">
              <div className="mb-4">
                <div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
              </div>
              <div className="text-sm mb-2">Loading PDF…</div>
              <div className="w-64 bg-gray-200 rounded-full h-2">
                <div 
                  className="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                  style={{ width: `${progress}%` }}
                ></div>
              </div>
              <div className="text-xs text-gray-500 mt-1">{progress}%</div>
            </div>
          ) : error ? (
            <div className="w-full h-full flex flex-col items-center justify-center text-red-600">
              <div className="text-lg mb-2">⚠️</div>
              <div className="text-sm">{error}</div>
              <button 
                onClick={loadPdf}
                className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                Retry
              </button>
            </div>
          ) : (
            <iframe 
              title="pdf" 
              src={blobUrl || url} 
              className="w-full h-full border-0"
              onLoad={() => setProgress(100)}
            />
          )}
        </div>
      </div>
    </div>
  );
};

// Export preload function for use in other components
export { preloadPdf };



