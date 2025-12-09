import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import "../styles/PublishCompany.css";
import Header from "@/components/header.jsx";
import Footer from "@/components/footer.jsx";
import { TriangleAlert } from 'lucide-react';

function PublishCompanyPage() {
  const navigate = useNavigate();
  const { user, setUser } = useAuthStore();
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);

    const formData = {
      name: e.target.name.value,
      location: e.target.location.value,
      size: e.target.size.value,
      website: e.target.website.value,
      description: e.target.description.value,
    };

    try {
      const response = await fetch("http://localhost:8000/api/my-company", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (response.ok) {
        setUser({ ...user, company: data.company });
        navigate("/creer-votre-offre");
      } else {
        alert(data.error || "Erreur lors de la création");
      }
    } catch (error) {
      console.error("Erreur:", error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const isFirstCompany = user?.status === "recruiter" && !user?.company;

  return (
    <div className="page-container">
      <Header />

      <main className="main-content" onSubmit={handleSubmit}>
        <h1>Créer mon entreprise</h1>

        {isFirstCompany && (
          <div style={{
            backgroundColor: "#fff3cd",
            border: "1px solid #ffc107",
            borderRadius: "8px",
            padding: "15px",
            marginBottom: "20px",
            maxWidth: "700px",
            display: "flex",
            alignItems: "center",
            gap: "10px"
          }}>
            <TriangleAlert size={24} color="#856404" />
            <div>
              <strong style={{ display: "block" }}>Création obligatoire</strong>
              <p style={{ margin: "5px 0 0" }}>
                Vous devez créer votre entreprise pour accéder aux autres fonctionnalités.
              </p>
            </div>
          </div>
        )}

        <p>Présentez votre entreprise aux candidats et recrutez les meilleurs talents.</p>

        <form className="company-form" onSubmit={handleSubmit}>
          <input type="text" name="name" placeholder="Nom de l'entreprise *" required />
          <input type="text" name="location" placeholder="Ville" />

          <select name="size">
            <option value="">Taille de l'entreprise</option>
            <option value="startup">Startup (1-10 employés)</option>
            <option value="small">Petite (11-50 employés)</option>
            <option value="medium">Moyenne (51-250 employés)</option>
            <option value="large">Grande (251-1000 employés)</option>
            <option value="enterprise">Entreprise (1000+ employés)</option>
          </select>

          <input type="url" name="website" placeholder="Site web (https://...)" />
          <textarea name="description" placeholder="Description de l'entreprise" rows="6"></textarea>

          <button type="submit" disabled={isSubmitting}>
            {isSubmitting ? "Publication en cours..." : "Publier"}
          </button>
        </form>
      </main>

      <Footer />
    </div>
  );
}

export default PublishCompanyPage;