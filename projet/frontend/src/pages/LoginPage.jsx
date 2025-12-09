import { useState, useEffect } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import "../styles/LoginPage.css";
import Header from '@/components/header.jsx';
import Footer from '@/components/footer.jsx';
import { Eye } from 'lucide-react';
import { EyeOff } from 'lucide-react';

function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const { checkAuth, user } = useAuthStore();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPwd, setShowPwd] = useState(false);
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState("");

  const successMessage = location.state?.message;

  useEffect(() => {
    const burger = document.getElementById("burger");
    const navLinks = document.querySelector(".nav-links");

    if (!burger || !navLinks) return;

    const toggle = () => {
      navLinks.classList.toggle("active");
    };

    burger.addEventListener("click", toggle);
    return () => {
      burger.removeEventListener("click", toggle);
    };
  }, []);

  const validate = () => {
    if (!email || !password) {
      setErrorMsg("Veuillez saisir votre email et votre mot de passe.");
      return false;
    }
    if (!/^\S+@\S+\.\S+$/.test(email)) {
      setErrorMsg("Format d'email invalide.");
      return false;
    }
    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMsg("");

    if (!validate()) return;

    setLoading(true);
    try {
      const res = await fetch("http://localhost:8000/api/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ email, password }),
      });

      if (!res.ok) {
        let message = "Échec de connexion. Vérifiez vos identifiants.";
        try {
          const data = await res.json();
          if (data?.error) message = data.error;
        } catch (_) { }
        throw new Error(message);
      }

      await checkAuth();

      setTimeout(() => {
        const currentUser = useAuthStore.getState().user;

        const isAdmin = currentUser?.roles?.includes('ROLE_ADMIN');

        if (isAdmin) {
          navigate('/admin', { replace: true });
        } else {
          const from = location.state?.from?.pathname || "/";
          navigate(from, { replace: true });
        }
      }, 100);

    } catch (err) {
      setErrorMsg(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <Header />
      <main className="login-page">
        <section className="login-card" aria-labelledby="login-title">

          {successMessage && (
            <div className="alert alert-success" role="status" aria-live="polite">
              {successMessage}
            </div>
          )}

          {errorMsg && (
            <div className="alert" role="alert" aria-live="assertive">
              {errorMsg}
            </div>
          )}

          <form className="login-form" onSubmit={handleSubmit} noValidate>
            <h1 id="login-title">Connexion</h1>
            <div className="form-field">
              <label htmlFor="email">Email</label>
              <input
                id="email"
                type="email"
                autoComplete="email"
                placeholder="vous@exemple.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                disabled={loading}
                required
              />
            </div>

            <div className="form-field">
              <label htmlFor="password">Mot de passe</label>
              <div className="relative password-wrap">
                <input
                  id="password"
                  type={showPwd ? "text" : "password"}
                  autoComplete="current-password"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  disabled={loading}
                  required
                />
                <button
                  type="button"
                  className="button-eye"
                  onClick={() => setShowPwd((s) => !s)}
                  aria-label={showPwd ? "Masquer le mot de passe" : "Afficher le mot de passe"}
                  aria-pressed={showPwd}
                  disabled={loading}
                >
                  {showPwd ? (
                    <EyeOff />
                  ) : (
                    <Eye />
                  )}
                </button>
              </div>
            </div>

            <div className="form-actions">
              <button className="button-primary" type="submit" disabled={loading}>
                {loading ? "Connexion…" : "Se connecter"}
              </button>
            </div>
          </form>

          <div className="login-footer">
            <Link to="/forgot-password">Mot de passe oublié ?</Link>
            <p>
              Pas encore de compte ?{" "}
              <Link to="/register">S'inscrire</Link>
            </p>
          </div>
        </section>
      </main>
      <Footer />
    </>
  );
}

export default LoginPage;