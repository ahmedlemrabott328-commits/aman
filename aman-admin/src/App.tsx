import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from '@/contexts/AuthContext';
import { ProtectedRoute } from '@/components/ProtectedRoute';
import { AppLayout } from '@/components/layout/AppLayout';
import { LoginPage } from '@/pages/LoginPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { CustomersPage } from '@/pages/CustomersPage';
import { CaptainsPage } from '@/pages/CaptainsPage';
import { TripsPage } from '@/pages/TripsPage';
import { PricingPage } from '@/pages/PricingPage';
import { CitiesPage } from '@/pages/CitiesPage';
import { WalletsPage } from '@/pages/WalletsPage';

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />

          <Route
            path="/"
            element={
              <ProtectedRoute>
                <AppLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<DashboardPage />} />
            <Route path="customers" element={<CustomersPage />} />
            <Route path="captains" element={<CaptainsPage />} />
            <Route path="trips" element={<TripsPage />} />
            <Route path="pricing" element={<PricingPage />} />
            <Route path="cities" element={<CitiesPage />} />
            <Route path="wallets" element={<WalletsPage />} />
          </Route>
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
