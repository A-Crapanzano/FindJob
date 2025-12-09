import { MapPin, Users, Mail } from "lucide-react";
import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import "../styles/ProfilPage.css";
import Footer from "@/components/footer.jsx";
import Header from "@/components/header.jsx";

function ProfilPage() {
  const { logout } = useAuthStore();
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const checkProfile = async () => {
    try {
      const response = await fetch("http://localhost:8000/api/profile", {
        method: "GET",
        credentials: "include",
      });

      if (response.ok) {
        const data = await response.json();
        setUser(data);
      } else {
        console.error("Erreur de récupération du profil:", response.status);
      }
    } catch (err) {
      console.error("Erreur:", err);
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    fetch("http://localhost:8000/api/logout", {
      method: "POST",
      credentials: "include",
    }).catch(console.error);
    logout();
    window.location.href = "/login";
  };

  useEffect(() => {
    checkProfile();
  }, []);

  return (
    <div className="page-container">
      <Header />

      <main className="main-content">
        <section className="card-content">
          {loading && <p>Chargement...</p>}

          {!loading && user && (
            <div className="card-text">
              <h2>
                <Users size={28} className="icon" /> Informations clés
              </h2>

              <div className="info-grid">
                <div className="info-item">
                  <h5>
                    <Users size={20} className="icon-small" />
                    Identité
                  </h5>
                  <div className="info-content">
                    <p>{user.firstname} {user.lastname}</p>
                    <p className="info-meta">
                      Inscrit le {new Date(user.createdAt).toLocaleDateString()}
                    </p>
                  </div>
                </div>

                <div className="info-item">
                  <h5>
                    <MapPin size={20} className="icon-small" />
                    Localisation
                  </h5>
                  <div className="info-content">
                    <p>{user.city}</p>
                    <p className="info-meta">{user.zipcode}</p>
                  </div>
                </div>

                <div className="info-item">
                  <h5>
                    <Mail size={20} className="icon-small" />
                    Email
                  </h5>
                  <div className="info-content">
                    <p>{user.email}</p>
                  </div>
                </div>
              </div>

              <div className="job-card-footer">
                <button className="button-offer" onClick={handleLogout}>
                  <Users size={20} style={{ marginRight: "8px" }} />
                  Déconnexion
                </button>
              </div>
            </div>
          )}

          {!loading && !user && <p>Profil introuvable.</p>}
        </section>
      </main>

      <Footer />
    </div>
  );
}

export default ProfilPage;