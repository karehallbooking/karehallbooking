import React, { useState, useEffect } from 'react';
import { FirestoreService } from '../../services/firestoreService';
import { AdminLayout } from '../../components/AdminLayout';
import { User as UserType } from '../../types';
import { UsersGrid } from '../../components/UsersGrid';

export function AdminProfile() {
  const [users, setUsers] = useState<UserType[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadUsers();
  }, []);

  const loadUsers = async () => {
    try {
      setLoading(true);
      const usersData = await FirestoreService.getAllUsers();
      setUsers(usersData);
    } catch (error) {
      console.error('Error loading users:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <AdminLayout>
      <UsersGrid 
        users={users} 
        loading={loading} 
        onRefresh={loadUsers} 
      />
    </AdminLayout>
  );
}
