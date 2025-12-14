import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import LiveTvPage from './pages/LiveTvPage';
import MoviesPage from './pages/MoviesPage';
import SeriesPage from './pages/SeriesPage';
import SeriesDetailsPage from './pages/SeriesDetailsPage';
import PlayerPage from './pages/PlayerPage';
import useAuthStore from './store/useAuthStore';

const ProtectedRoute = ({ children }) => {
  const isAuthenticated = useAuthStore(state => state.isAuthenticated);
  return isAuthenticated ? children : <Navigate to="/login" replace />;
};

function App() {
  return (
    <Router>
      <div className="min-h-screen w-full bg-neutral-950 text-white font-sans antialiased selection:bg-red-500/30">
        <Routes>
          <Route path="/login" element={<LoginPage />} />

          <Route path="/dashboard" element={
            <ProtectedRoute>
              <DashboardPage />
            </ProtectedRoute>
          } />

          <Route path="/live" element={
            <ProtectedRoute>
              <LiveTvPage />
            </ProtectedRoute>
          } />

          <Route path="/movies" element={
            <ProtectedRoute>
              <MoviesPage />
            </ProtectedRoute>
          } />

          <Route path="/series" element={
            <ProtectedRoute>
              <SeriesPage />
            </ProtectedRoute>
          } />

          <Route path="/series/:seriesId" element={
            <ProtectedRoute>
              <SeriesDetailsPage />
            </ProtectedRoute>
          } />

          <Route path="/player/:type/:streamId" element={
            <ProtectedRoute>
              <PlayerPage />
            </ProtectedRoute>
          } />

          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;
