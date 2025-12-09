import { useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import "../styles/ApplyPage.css";
import Header from "@/components/header.jsx";
import Footer from "@/components/footer.jsx";

function ApplyPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const jobId = searchParams.get("jobId");
  const { user } = useAuthStore();
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);

    const formData = new FormData();
    formData.append("message", e.target.message.value);

    const resumeFile = e.target.resume.files[0];
    if (resumeFile) {
      formData.append("resume", resumeFile);
    }

    try {
      const response = await fetch(`http://localhost:8000/api/job-offers/${jobId}/apply`, {
        method: "POST",
        credentials: "include",
        body: formData,
      });

      const data = await response.json();

      if (response.ok) {
        navigate("/mes-candidatures");
      } else {
        console.error("Erreur:", data.error);
      }
    } catch (error) {
      console.error("Erreur:", error);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="page-container">
      <Header />

      <main className="main-content">
        <h1>Postuler à l'offre</h1>
        <p>Remplissez le formulaire ci-dessous pour envoyer votre candidature.</p>

        <form className="apply-form" onSubmit={handleSubmit}>
          <label>
            Message de motivation
            <textarea
              name="message"
              placeholder="Expliquez pourquoi vous êtes le candidat idéal..."
              rows="6"
            ></textarea>
          </label>

          <label className="file-label">
            Joindre votre CV (fichier PDF uniquement)
            <input type="file" name="resume" accept=".pdf,.doc,.docx" />
          </label>

          <button type="submit" disabled={isSubmitting}>
            {isSubmitting ? "Envoi en cours..." : "Envoyer la candidature"}
          </button>
        </form>
      </main>

      <Footer />
    </div>
  );
}

export default ApplyPage;