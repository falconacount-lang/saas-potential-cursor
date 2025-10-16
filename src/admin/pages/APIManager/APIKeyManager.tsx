import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { 
  Key, 
  Eye, 
  EyeOff, 
  Check, 
  X, 
  AlertTriangle, 
  RefreshCw, 
  Plus, 
  Trash2,
  TrendingUp,
  DollarSign,
  Activity,
  Settings
} from 'lucide-react';
import { toast } from 'sonner';

interface APIKey {
  id: number;
  service_name: string;
  display_name: string;
  api_key: string;
  status: 'active' | 'inactive' | 'testing' | 'error';
  is_enabled: boolean;
  usage_count: number;
  error_count: number;
  last_tested: string | null;
  test_result: string | null;
  test_response_time_ms: number;
  month_spend: number;
  month_budget: number;
  budget_used_percent: number;
  error_rate_percent: number;
}

interface UsageStats {
  service_name: string;
  year: number;
  month: number;
  budget_limit: number;
  current_spend: number;
  total_requests: number;
  successful_requests: number;
  failed_requests: number;
  budget_used_percent: number;
}

interface TestResult {
  status: string;
  message: string;
  response_time: number;
  details: any;
}

const APIKeyManager: React.FC = () => {
  const [apiKeys, setApiKeys] = useState<APIKey[]>([]);
  const [loading, setLoading] = useState(true);
  const [testing, setTesting] = useState<Record<string, boolean>>({});
  const [showAddForm, setShowAddForm] = useState(false);
  const [selectedKey, setSelectedKey] = useState<APIKey | null>(null);
  const [showKey, setShowKey] = useState<Record<string, boolean>>({});
  const [activeTab, setActiveTab] = useState('overview');
  
  const [newAPIKey, setNewAPIKey] = useState({
    service_name: '',
    display_name: '',
    api_key: '',
    api_secret: '',
    webhook_url: '',
    additional_config: ''
  });

  useEffect(() => {
    fetchAPIKeys();
  }, []);

  const fetchAPIKeys = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/admin/api-keys`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();
      
      if (data.success) {
        setApiKeys(data.data);
      } else {
        toast.error('Failed to fetch API keys');
      }
    } catch (error) {
      console.error('Error fetching API keys:', error);
      toast.error('Failed to load API keys');
    } finally {
      setLoading(false);
    }
  };

  const testAPIKey = async (serviceName: string) => {
    setTesting(prev => ({ ...prev, [serviceName]: true }));
    try {
      const token = localStorage.getItem('token');
      const response = await fetch(
        `${import.meta.env.VITE_API_BASE_URL}/api/admin/api-keys/${serviceName}/test`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        }
      );

      const data = await response.json();
      
      if (data.success) {
        toast.success(`${serviceName} API test successful!`);
        await fetchAPIKeys(); // Refresh list
      } else {
        toast.error(`${serviceName} API test failed: ${data.data?.message || 'Unknown error'}`);
      }
    } catch (error) {
      console.error('Error testing API key:', error);
      toast.error('Failed to test API key');
    } finally {
      setTesting(prev => ({ ...prev, [serviceName]: false }));
    }
  };

  const saveAPIKey = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const token = localStorage.getItem('token');
      
      // Parse additional config if present
      let additionalConfig = null;
      if (newAPIKey.additional_config) {
        try {
          additionalConfig = JSON.parse(newAPIKey.additional_config);
        } catch (e) {
          toast.error('Invalid JSON in additional config');
          return;
        }
      }

      const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/admin/api-keys`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          ...newAPIKey,
          additional_config: additionalConfig,
          auto_test: true
        })
      });

      const data = await response.json();
      
      if (data.success) {
        toast.success('API key saved successfully!');
        setShowAddForm(false);
        setNewAPIKey({
          service_name: '',
          display_name: '',
          api_key: '',
          api_secret: '',
          webhook_url: '',
          additional_config: ''
        });
        await fetchAPIKeys();
      } else {
        toast.error(`Failed to save API key: ${data.message}`);
      }
    } catch (error) {
      console.error('Error saving API key:', error);
      toast.error('Failed to save API key');
    }
  };

  const toggleAPIKey = async (serviceName: string, enabled: boolean) => {
    try {
      const token = localStorage.getItem('token');
      const response = await fetch(
        `${import.meta.env.VITE_API_BASE_URL}/api/admin/api-keys/${serviceName}/toggle`,
        {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ enabled })
        }
      );

      const data = await response.json();
      
      if (data.success) {
        toast.success(`API key ${enabled ? 'enabled' : 'disabled'}`);
        await fetchAPIKeys();
      } else {
        toast.error(`Failed to toggle API key: ${data.message}`);
      }
    } catch (error) {
      console.error('Error toggling API key:', error);
      toast.error('Failed to toggle API key');
    }
  };

  const deleteAPIKey = async (serviceName: string) => {
    if (!confirm(`Are you sure you want to delete the ${serviceName} API key?`)) {
      return;
    }

    try {
      const token = localStorage.getItem('token');
      const response = await fetch(
        `${import.meta.env.VITE_API_BASE_URL}/api/admin/api-keys/${serviceName}`,
        {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        }
      );

      const data = await response.json();
      
      if (data.success) {
        toast.success('API key deleted successfully');
        await fetchAPIKeys();
      } else {
        toast.error(`Failed to delete API key: ${data.message}`);
      }
    } catch (error) {
      console.error('Error deleting API key:', error);
      toast.error('Failed to delete API key');
    }
  };

  const getStatusBadge = (status: string) => {
    const statusConfig = {
      active: { color: 'bg-green-500', text: 'Active' },
      inactive: { color: 'bg-gray-500', text: 'Inactive' },
      testing: { color: 'bg-yellow-500', text: 'Testing' },
      error: { color: 'bg-red-500', text: 'Error' }
    };
    
    const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.inactive;
    return <Badge className={`${config.color} text-white`}>{config.text}</Badge>;
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount || 0);
  };

  const calculateTotalSpend = () => {
    return apiKeys.reduce((sum, key) => sum + (key.month_spend || 0), 0);
  };

  const calculateTotalBudget = () => {
    return apiKeys.reduce((sum, key) => sum + (key.month_budget || 0), 0);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <RefreshCw className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  return (
    <div className="space-y-6 p-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">API Key Management</h1>
          <p className="text-muted-foreground mt-1">
            Manage third-party API integrations and monitor usage
          </p>
        </div>
        <Button onClick={() => setShowAddForm(true)} className="gap-2">
          <Plus className="h-4 w-4" />
          Add API Key
        </Button>
      </div>

      {/* Overview Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total APIs</CardTitle>
            <Key className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{apiKeys.length}</div>
            <p className="text-xs text-muted-foreground">
              {apiKeys.filter(k => k.is_enabled).length} active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Monthly Spend</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatCurrency(calculateTotalSpend())}</div>
            <p className="text-xs text-muted-foreground">
              of {formatCurrency(calculateTotalBudget())} budget
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Requests</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {apiKeys.reduce((sum, k) => sum + (k.usage_count || 0), 0).toLocaleString()}
            </div>
            <p className="text-xs text-muted-foreground">
              This month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Success Rate</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {(() => {
                const total = apiKeys.reduce((sum, k) => sum + (k.usage_count || 0), 0);
                const errors = apiKeys.reduce((sum, k) => sum + (k.error_count || 0), 0);
                const rate = total > 0 ? ((total - errors) / total) * 100 : 100;
                return rate.toFixed(1) + '%';
              })()}
            </div>
            <p className="text-xs text-muted-foreground">
              Across all APIs
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Add API Key Dialog */}
      <Dialog open={showAddForm} onOpenChange={setShowAddForm}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Add New API Key</DialogTitle>
            <DialogDescription>
              Configure a new third-party API integration
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={saveAPIKey} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="service_name">Service Name *</Label>
                <Input
                  id="service_name"
                  placeholder="e.g., openai, stripe, linkedin"
                  value={newAPIKey.service_name}
                  onChange={(e) => setNewAPIKey(prev => ({ ...prev, service_name: e.target.value }))}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="display_name">Display Name *</Label>
                <Input
                  id="display_name"
                  placeholder="e.g., OpenAI GPT-4"
                  value={newAPIKey.display_name}
                  onChange={(e) => setNewAPIKey(prev => ({ ...prev, display_name: e.target.value }))}
                  required
                />
              </div>
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="api_key">API Key *</Label>
              <Input
                id="api_key"
                type="password"
                placeholder="Enter API key"
                value={newAPIKey.api_key}
                onChange={(e) => setNewAPIKey(prev => ({ ...prev, api_key: e.target.value }))}
                required
              />
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="api_secret">API Secret (optional)</Label>
              <Input
                id="api_secret"
                type="password"
                placeholder="Enter API secret if required"
                value={newAPIKey.api_secret}
                onChange={(e) => setNewAPIKey(prev => ({ ...prev, api_secret: e.target.value }))}
              />
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="webhook_url">Webhook URL (optional)</Label>
              <Input
                id="webhook_url"
                placeholder="https://yoursite.com/webhooks/service"
                value={newAPIKey.webhook_url}
                onChange={(e) => setNewAPIKey(prev => ({ ...prev, webhook_url: e.target.value }))}
              />
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="additional_config">Additional Config (JSON, optional)</Label>
              <Textarea
                id="additional_config"
                placeholder='{"key": "value"}'
                value={newAPIKey.additional_config}
                onChange={(e) => setNewAPIKey(prev => ({ ...prev, additional_config: e.target.value }))}
                rows={3}
              />
            </div>
            
            <div className="flex gap-2 justify-end">
              <Button type="button" variant="outline" onClick={() => setShowAddForm(false)}>
                Cancel
              </Button>
              <Button type="submit">
                Save & Test API Key
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>

      {/* API Keys List */}
      <div className="grid gap-4">
        {apiKeys.length === 0 ? (
          <Card>
            <CardContent className="flex flex-col items-center justify-center py-12">
              <Key className="h-12 w-12 text-muted-foreground mb-4" />
              <h3 className="text-lg font-semibold mb-2">No API Keys Configured</h3>
              <p className="text-muted-foreground text-center mb-4">
                Add your first API key to start integrating with third-party services
              </p>
              <Button onClick={() => setShowAddForm(true)}>
                <Plus className="h-4 w-4 mr-2" />
                Add API Key
              </Button>
            </CardContent>
          </Card>
        ) : (
          apiKeys.map((apiKey) => (
            <Card key={apiKey.id} className={!apiKey.is_enabled ? 'opacity-60' : ''}>
              <CardContent className="p-6">
                <div className="flex items-start justify-between">
                  <div className="space-y-3 flex-1">
                    <div className="flex items-center gap-3">
                      <h3 className="text-xl font-semibold">{apiKey.display_name}</h3>
                      {getStatusBadge(apiKey.status)}
                      {!apiKey.is_enabled && (
                        <Badge variant="outline" className="text-muted-foreground">
                          Disabled
                        </Badge>
                      )}
                    </div>
                    
                    <div className="flex items-center gap-4 text-sm text-muted-foreground">
                      <span className="font-mono">{apiKey.service_name}</span>
                      <span>•</span>
                      <span>Usage: {apiKey.usage_count.toLocaleString()}</span>
                      {apiKey.error_count > 0 && (
                        <>
                          <span>•</span>
                          <span className="text-red-500">
                            Errors: {apiKey.error_count} ({apiKey.error_rate_percent.toFixed(1)}%)
                          </span>
                        </>
                      )}
                    </div>

                    {apiKey.month_spend > 0 && (
                      <div className="flex items-center gap-2">
                        <div className="flex-1">
                          <div className="flex justify-between text-sm mb-1">
                            <span>Monthly Budget</span>
                            <span className="font-medium">
                              {formatCurrency(apiKey.month_spend)} / {formatCurrency(apiKey.month_budget)}
                            </span>
                          </div>
                          <div className="w-full bg-gray-200 rounded-full h-2">
                            <div
                              className={`h-2 rounded-full ${
                                apiKey.budget_used_percent > 90 ? 'bg-red-500' :
                                apiKey.budget_used_percent > 70 ? 'bg-yellow-500' :
                                'bg-green-500'
                              }`}
                              style={{ width: `${Math.min(apiKey.budget_used_percent, 100)}%` }}
                            />
                          </div>
                        </div>
                      </div>
                    )}

                    {apiKey.last_tested && (
                      <p className="text-xs text-muted-foreground">
                        Last tested: {new Date(apiKey.last_tested).toLocaleString()}
                        {apiKey.test_response_time_ms > 0 && (
                          <span> ({apiKey.test_response_time_ms}ms)</span>
                        )}
                      </p>
                    )}
                    
                    {apiKey.test_result && (
                      <p className="text-xs text-muted-foreground">
                        {apiKey.test_result}
                      </p>
                    )}
                  </div>

                  <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-2">
                      <Switch
                        checked={apiKey.is_enabled}
                        onCheckedChange={(checked) => toggleAPIKey(apiKey.service_name, checked)}
                      />
                      <span className="text-sm">Enabled</span>
                    </div>
                    
                    <Button
                      onClick={() => testAPIKey(apiKey.service_name)}
                      disabled={testing[apiKey.service_name]}
                      variant="outline"
                      size="sm"
                    >
                      {testing[apiKey.service_name] ? (
                        <>
                          <RefreshCw className="h-3 w-3 mr-2 animate-spin" />
                          Testing...
                        </>
                      ) : (
                        <>
                          <RefreshCw className="h-3 w-3 mr-2" />
                          Test API
                        </>
                      )}
                    </Button>
                    
                    <Button
                      onClick={() => deleteAPIKey(apiKey.service_name)}
                      variant="outline"
                      size="sm"
                      className="text-red-500 hover:text-red-700"
                    >
                      <Trash2 className="h-3 w-3 mr-2" />
                      Delete
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>
    </div>
  );
};

export default APIKeyManager;
