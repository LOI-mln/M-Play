import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import LiveTvPage from './pages/LiveTvPage';
import PlayerPage from './pages/PlayerPage'; // Placeholder
import useAuthStore from './store/useAuthStore';

// Temporary Player Placeholder
const PlayerPlaceholder = () => <div className="text-white p-10">Video Player Loading...</div>;

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

          <Route path="/player/live/:streamId" element={
            <ProtectedRoute>
              {/* Implemented in next step */}
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
