import { Navigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '@/store/authStore';

function AdminProtectedRoute({ children }) {
    const { user, isAuthenticated, loading } = useAuthStore();
    const location = useLocation();

    if (loading) {
        return (
            <div style={{
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                height: '100vh'
            }}>
                <p>Chargement...</p>
            </div>
        );
    }

    if (!isAuthenticated) {
        return <Navigate to="/login" state={{ from: location }} replace />;
    }

    const isAdmin = user?.roles?.includes('ROLE_ADMIN');

    if (!isAdmin) {
        return <Navigate to="/" replace />;
    }

    return children;
}

export default AdminProtectedRoute;