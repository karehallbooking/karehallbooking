import React, { useState, useEffect } from 'react';
import { Building, Plus, Edit, Trash2, Users, Wifi, Car, Coffee } from 'lucide-react';
import { FirestoreService } from '../../services/firestoreService';
import { Hall } from '../../types';
import { AdminLayout } from '../../components/AdminLayout';

export function HallManagement() {
  const [halls, setHalls] = useState<Hall[]>([]);
  const [loading, setLoading] = useState(true);
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingHall, setEditingHall] = useState<Hall | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    capacity: '',
    facilities: '',
    image: '',
    available: true
  });
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    loadHalls();
  }, []);

  const loadHalls = async () => {
    try {
      setLoading(true);
      const hallsData = await FirestoreService.getHalls();
      setHalls(hallsData);
    } catch (error) {
      console.error('Error loading halls:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value, type } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? (e.target as HTMLInputElement).checked : value
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name.trim() || !formData.capacity.trim()) {
      alert('Please fill in all required fields');
      return;
    }

    setSaving(true);
    try {
      const facilities = formData.facilities
        .split(',')
        .map(f => f.trim())
        .filter(f => f.length > 0);

      const hallData = {
        name: formData.name.trim(),
        capacity: parseInt(formData.capacity),
        facilities,
        image: formData.image.trim() || 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=500&h=300&fit=crop',
        available: formData.available
      };

      if (editingHall) {
        // Update existing hall
        await FirestoreService.updateHall(editingHall.id!, hallData);
        setEditingHall(null);
      } else {
        // Create new hall
        await FirestoreService.createHall(hallData);
      }

      // Reset form and reload halls
      setFormData({
        name: '',
        capacity: '',
        facilities: '',
        image: '',
        available: true
      });
      setShowAddForm(false);
      await loadHalls();
      
      alert(editingHall ? 'Hall updated successfully!' : 'Hall added successfully!');
    } catch (error) {
      console.error('Error saving hall:', error);
      alert('Failed to save hall. Please try again.');
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (hall: Hall) => {
    setEditingHall(hall);
    setFormData({
      name: hall.name,
      capacity: hall.capacity.toString(),
      facilities: hall.facilities.join(', '),
      image: hall.image || '',
      available: hall.available
    });
    setShowAddForm(true);
  };

  const handleDelete = async (hallId: string) => {
    if (!confirm('Are you sure you want to delete this hall?')) {
      return;
    }

    try {
      await FirestoreService.deleteHall(hallId);
      await loadHalls();
      alert('Hall deleted successfully!');
    } catch (error) {
      console.error('Error deleting hall:', error);
      alert('Failed to delete hall. Please try again.');
    }
  };

  const handleCancel = () => {
    setFormData({
      name: '',
      capacity: '',
      facilities: '',
      image: '',
      available: true
    });
    setEditingHall(null);
    setShowAddForm(false);
  };

  const getFacilityIcon = (facility: string) => {
    const facilityLower = facility.toLowerCase();
    if (facilityLower.includes('wifi') || facilityLower.includes('internet')) {
      return <Wifi className="h-4 w-4" />;
    } else if (facilityLower.includes('parking')) {
      return <Car className="h-4 w-4" />;
    } else if (facilityLower.includes('catering') || facilityLower.includes('food')) {
      return <Coffee className="h-4 w-4" />;
    } else {
      return <Building className="h-4 w-4" />;
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <AdminLayout>
      <div className="space-y-6">
      <div className="bg-white rounded-lg shadow-sm p-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">Hall Management</h1>
            <p className="text-gray-600">Manage available halls and their facilities</p>
          </div>
          <button
            onClick={() => setShowAddForm(true)}
            className="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Plus className="h-4 w-4" />
            <span>Add Hall</span>
          </button>
        </div>
      </div>

      {halls.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {halls.map((hall) => (
            <div key={hall.id} className="bg-white rounded-lg shadow-sm overflow-hidden">
              <div className="h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center relative">
                {hall.image ? (
                  <img 
                    src={hall.image} 
                    alt={hall.name}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <Building className="h-16 w-16 text-white" />
                )}
              </div>
              
              <div className="p-6">
                <div className="flex justify-between items-start mb-4">
                  <div>
                    <h3 className="text-lg font-semibold text-gray-800">{hall.name}</h3>
                    <div className="flex items-center space-x-2 mt-1">
                      <Users className="h-4 w-4 text-gray-400" />
                      <span className="text-sm text-gray-600">Capacity: {hall.capacity} people</span>
                    </div>
                  </div>
                  <div className="flex space-x-2">
                    <button
                      onClick={() => handleEdit(hall)}
                      className="p-2 text-gray-400 hover:text-blue-600 transition-colors"
                    >
                      <Edit className="h-4 w-4" />
                    </button>
                    <button 
                      onClick={() => handleDelete(hall.id!)}
                      className="p-2 text-gray-400 hover:text-red-600 transition-colors"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </div>

                <div className="mb-4">
                  <p className="text-sm font-medium text-gray-700 mb-2">Facilities:</p>
                  <div className="flex flex-wrap gap-2">
                    {hall.facilities.map((facility, index) => (
                      <div key={index} className="flex items-center space-x-1 px-2 py-1 bg-gray-100 rounded-full">
                        {getFacilityIcon(facility)}
                        <span className="text-xs text-gray-600">{facility}</span>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="flex items-center justify-between">
                  <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                    hall.available 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-red-100 text-red-800'
                  }`}>
                    {hall.available ? 'Available' : 'Unavailable'}
                  </span>
                  <button className="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    View Details
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow-sm p-12 text-center">
          <Building className="h-16 w-16 text-gray-400 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-800 mb-2">No halls available</h3>
          <p className="text-gray-500 mb-4">Add halls to get started with hall management</p>
          <button
            onClick={() => setShowAddForm(true)}
            className="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors mx-auto"
          >
            <Plus className="h-4 w-4" />
            <span>Add First Hall</span>
          </button>
        </div>
      )}

      {/* Add/Edit Hall Form Modal */}
      {showAddForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">
              {editingHall ? 'Edit Hall' : 'Add New Hall'}
            </h3>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Hall Name *</label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter hall name"
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Capacity *</label>
                <input
                  type="number"
                  name="capacity"
                  value={formData.capacity}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter capacity"
                  min="1"
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Facilities</label>
                <textarea
                  name="facilities"
                  value={formData.facilities}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter facilities (comma separated)"
                  rows={3}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                <input
                  type="url"
                  name="image"
                  value={formData.image}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter image URL (optional)"
                />
              </div>
              <div className="flex items-center">
                <input
                  type="checkbox"
                  name="available"
                  checked={formData.available}
                  onChange={handleInputChange}
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label className="ml-2 block text-sm text-gray-700">
                  Available for booking
                </label>
              </div>
              <div className="flex space-x-3">
                <button
                  type="button"
                  onClick={handleCancel}
                  className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {saving ? 'Saving...' : (editingHall ? 'Update Hall' : 'Add Hall')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
      </div>
    </AdminLayout>
  );
}
