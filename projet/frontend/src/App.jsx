import { useEffect } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import HomePage from '@pages/HomePage';
import RegisterPage from '@/pages/RegisterPage';
import OfferPage from '@/pages/OfferPage';
import CompanyPage from '@/pages/CompanyPage';
import ProfilPage from '@/pages/ProfilPage';
import PublishCompany from '@/pages/PublishCompany';
import PublishOffer from '@/pages/PublishOffer';
import MyApplications from '@/pages/MyApplications';
import ApplyPage from '@/pages/ApplyPage';
import LoginPage from '@/pages/LoginPage';
import AdminDashboard from '@/pages/AdminDashboard';
import ProtectedRoute from '@/components/ProtectedRoute';
import AdminProtectedRoute from '@/components/AdminProtectedRoute';
import { useAuthStore } from './store/authStore';
import MyOffers from './pages/MyOffers';
import JobApplications from './pages/JobApplications';


function App() {
  const checkAuth = useAuthStore((state) => state.checkAuth);

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/jobs/:id" element={<OfferPage />} />
        <Route path="/CompanyPage/:id" element={<CompanyPage />} />

        <Route
          path="/admin"
          element={
            <AdminProtectedRoute>
              <AdminDashboard />
            </AdminProtectedRoute>
          }
        />

        <Route
          path="/creer-votre-entreprise"
          element={
            <ProtectedRoute>
              <PublishCompany />
            </ProtectedRoute>
          }
        />
        <Route
          path="/ProfilPage"
          element={
            <ProtectedRoute>
              <ProfilPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/publier-votre-offre"
          element={
            <ProtectedRoute>
              <PublishOffer />
            </ProtectedRoute>
          }
        />
        <Route
          path="/mes-candidatures"
          element={
            <ProtectedRoute>
              <MyApplications />
            </ProtectedRoute>
          }
        />
        <Route
          path="/mes-offres"
          element={
            <ProtectedRoute>
              <MyOffers />
            </ProtectedRoute>
          }
        />
        <Route
          path="/postuler"
          element={
            <ProtectedRoute>
              <ApplyPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/jobs/:id/applications"
          element={
            <ProtectedRoute>
              <JobApplications />
            </ProtectedRoute>
          }
        />
      </Routes>
    </BrowserRouter>
  );
}

export default App;