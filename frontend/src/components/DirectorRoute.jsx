import { Navigate, useLocation } from 'react-router-dom';

function getUser() {
  try {
    return JSON.parse(localStorage.getItem('user') || 'null');
  } catch {
    return null;
  }
}

export default function DirectorRoute({ children }) {
  const location = useLocation();
  const token = localStorage.getItem('token');
  const user = getUser();

  if (!token) return <Navigate to="/login" replace state={{ from: location }} />;
  if (user?.role !== 'directeur') return <Navigate to="/consultations" replace />;

  return children;
}
