import React, { useState } from 'react';
import { AdminLayout } from '../../components/AdminLayout';
import { checkForDuplicateAccounts, cleanupDuplicateAccounts } from '../../utils/cleanupDuplicates';

export function CleanupDuplicates() {
  const [loading, setLoading] = useState(false);
  const [duplicates, setDuplicates] = useState<{ [email: string]: any[] }>({});
  const [message, setMessage] = useState('');

  const checkDuplicates = async () => {
    setLoading(true);
    setMessage('');
    try {
      const duplicateGroups = await checkForDuplicateAccounts();
      setDuplicates(duplicateGroups);
      
      const totalDuplicates = Object.keys(duplicateGroups).length;
      if (totalDuplicates > 0) {
        setMessage(`Found ${totalDuplicates} emails with duplicate accounts. Click "Clean Up" to remove duplicates.`);
      } else {
        setMessage('No duplicate accounts found!');
      }
    } catch (error) {
      setMessage(`Error checking duplicates: ${error}`);
    } finally {
      setLoading(false);
    }
  };

  const cleanUpDuplicates = async () => {
    setLoading(true);
    setMessage('');
    try {
      await cleanupDuplicateAccounts();
      setMessage('✅ Successfully cleaned up all duplicate accounts!');
      setDuplicates({});
    } catch (error) {
      setMessage(`Error cleaning up duplicates: ${error}`);
    } finally {
      setLoading(false);
    }
  };

  return (
    <AdminLayout>
      <div className="max-w-4xl mx-auto space-y-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Cleanup Duplicate Accounts</h1>
          <p className="text-gray-600 mt-2">Find and remove duplicate user accounts with the same email address</p>
        </div>

        <div className="bg-white rounded-lg shadow-md p-6">
          <div className="space-y-4">
            <div className="flex space-x-4">
              <button
                onClick={checkDuplicates}
                disabled={loading}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {loading ? 'Checking...' : 'Check for Duplicates'}
              </button>
              
              {Object.keys(duplicates).length > 0 && (
                <button
                  onClick={cleanUpDuplicates}
                  disabled={loading}
                  className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                >
                  {loading ? 'Cleaning...' : 'Clean Up Duplicates'}
                </button>
              )}
            </div>

            {message && (
              <div className={`p-4 rounded-lg ${
                message.includes('Error') 
                  ? 'bg-red-50 border border-red-200 text-red-700'
                  : message.includes('✅')
                  ? 'bg-green-50 border border-green-200 text-green-700'
                  : 'bg-blue-50 border border-blue-200 text-blue-700'
              }`}>
                {message}
              </div>
            )}

            {Object.keys(duplicates).length > 0 && (
              <div className="mt-6">
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Duplicate Accounts Found:</h3>
                <div className="space-y-4">
                  {Object.entries(duplicates).map(([email, users]) => (
                    <div key={email} className="border border-gray-200 rounded-lg p-4">
                      <h4 className="font-medium text-gray-800 mb-2">Email: {email}</h4>
                      <div className="space-y-2">
                        {users.map((user, index) => (
                          <div key={user.id} className="flex items-center justify-between bg-gray-50 p-3 rounded">
                            <div>
                              <span className="font-medium">{user.name || 'No name'}</span>
                              <span className="text-sm text-gray-600 ml-2">(UID: {user.id})</span>
                            </div>
                            <div className="text-sm text-gray-600">
                              Created: {user.createdAt?.toDate ? user.createdAt.toDate().toLocaleDateString() : 'Unknown'}
                              {index === 0 && <span className="ml-2 text-green-600 font-medium">(Will keep this one)</span>}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}

