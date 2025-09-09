import React from 'react';

interface NotificationModalProps {
  open: boolean;
  title?: string;
  message: string;
  confirmText?: string;
  onClose: () => void;
}

export function NotificationModal({ open, title, message, confirmText = 'OK', onClose }: NotificationModalProps) {
  if (!open) return null;
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black bg-opacity-40" onClick={onClose} />
      <div className="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        {title && <h3 className="text-lg font-semibold text-gray-800 mb-2">{title}</h3>}
        <p className="text-gray-800 font-bold text-center mb-6">{message}</p>
        <div className="flex justify-center">
          <button
            onClick={onClose}
            className="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark"
          >
            {confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}






