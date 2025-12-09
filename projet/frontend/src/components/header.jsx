import { useState } from "react";
import { Link } from "react-router-dom";
import { Users } from "lucide-react";
import { useAuthStore } from "@/store/authStore";
import "./header.css";
import { User } from 'lucide-react';

function Header() {
  const [menuOpen, setMenuOpen] = useState(false);

  const { isAuthenticated, loading, isCandidate, isRecruiter } = useAuthStore();

  const toggleMenu = () => {
    setMenuOpen(!menuOpen);
  };

  if (loading) {
    return (
      <header>
        <nav className="navbar">
          <div className="logo">
            <Link to="/">
              <img src="/assets/images/logo.png" alt="FindJob Logo" />
            </Link>
          </div>
        </nav>
      </header>
    );
  }

  const isUserCandidate = isCandidate();
  const isUserRecruiter = isRecruiter();

  return (
    <header>
      <nav className="navbar">
        <div className="logo">
          <img src="/assets/images/logo.png" alt="FindJob Logo" />
        </div>

        <div className="burger" onClick={toggleMenu}>
          <span></span>
          <span></span>
          <span></span>
        </div>

        <ul className={`nav-links ${menuOpen ? "active" : ""}`}>
          <li>
            <Link to="/">Accueil</Link>
          </li>

          {isAuthenticated && (
            <>
              {isUserCandidate && (
                <li>
                  <Link to="/mes-candidatures">Mes candidatures</Link>
                </li>
              )}

              {isUserRecruiter && (
                <>
                  <li>
                    <Link to="/publier-votre-offre">Publier une offre</Link>
                  </li>
                  <li>
                    <Link to="/mes-offres">Mes offres</Link>
                  </li>
                </>
              )}
            </>
          )}

          <li className="spacer"></li>

          {isAuthenticated ? (
            <li>
              <Link to="/ProfilPage"><Users size={30} className="user-icon" /></Link>
            </li>
          ) : (
            <>
              <li>
                <Link to="/login" className="button">
                  Se connecter
                </Link>
              </li>
              <li>
                <Link to="/register" className="button">
                  S'inscrire
                </Link>
              </li>
            </>
          )}
        </ul>
      </nav>
    </header>
  );
}

export default Header;