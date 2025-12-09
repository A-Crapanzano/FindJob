import { Navigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '@/store/authStore';

function ProtectedRoute({ children }) {
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
    if (isAdmin && location.pathname !== '/admin') {
        return <Navigate to="/admin" replace />;
    }

    if (
        user?.status === 'recruiter' &&
        !user?.company &&
        location.pathname !== '/creer-votre-entreprise'
    ) {
        return <Navigate to="/creer-votre-entreprise" replace />;
    }

    return children;
}

export default ProtectedRoute;