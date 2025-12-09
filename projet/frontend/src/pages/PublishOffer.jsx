import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuthStore } from "@/store/authStore";
import "../styles/PublishOffer.css";
import Header from "@/components/header.jsx";
import Footer from "@/components/footer.jsx";

function PublishOffer() {
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [requirements, setRequirements] = useState([""]);

  const addRequirement = () => {
    setRequirements([...requirements, ""]);
  };

  const removeRequirement = (index) => {
    if (requirements.length > 1) {
      setRequirements(requirements.filter((_, i) => i !== index));
    }
  };

  const changeRequirement = (index, value) => {
    const newRequirements = [...requirements];
    newRequirements[index] = value;
    setRequirements(newRequirements);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);

    const cleanRequirements = requirements
      .map(req => req.trim())
      .filter(req => req !== "");

    const formData = {
      title: e.target.title.value,
      location: e.target.location.value,
      contractType: e.target.contractType.value,
      remoteType: e.target.remoteType.value,
      salaryMin: e.target.salaryMin.value ? parseInt(e.target.salaryMin.value) : null,
      salaryMax: e.target.salaryMax.value ? parseInt(e.target.salaryMax.value) : null,
      description: e.target.description.value,
      requirements: cleanRequirements.length > 0 ? cleanRequirements : null,
    };

    try {
      const response = await fetch("http://localhost:8000/api/job-offers", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (response.ok) {
        navigate(`/jobs/${data.job.id}`);
      } else {
        alert(data.error || "Erreur lors de la création");
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
        <h1>Publier une offre d'emploi</h1>
        <p>Décrivez votre offre et trouvez le candidat idéal.</p>

        <form className="job-form" onSubmit={handleSubmit}>
          <input type="text" name="title" placeholder="Titre du poste" required />
          <input type="text" name="location" placeholder="Lieu (ville)" />

          <select name="contractType">
            <option value="">Type de contrat</option>
            <option value="CDI">CDI</option>
            <option value="CDD">CDD</option>
            <option value="Stage">Stage</option>
            <option value="Alternance">Alternance</option>
          </select>

          <select name="remoteType">
            <option value="onsite">Sur site</option>
            <option value="remote">100% télétravail</option>
            <option value="hybrid">Hybride</option>
          </select>

          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "20px" }}>
            <input type="number" name="salaryMin" placeholder="Salaire min (€/an)" min="0" />
            <input type="number" name="salaryMax" placeholder="Salaire max (€/an)" min="0" />
          </div>

          <textarea name="description" placeholder="Description du poste" rows="6" required></textarea>

          <div className="requirements-section">
            <label>Compétences requises</label>

            {requirements.map((requirement, index) => (
              <div key={index} className="requirement-item">
                <input
                  type="text"
                  placeholder="Ex: Maîtrise de React et TypeScript"
                  value={requirement}
                  onChange={(e) => changeRequirement(index, e.target.value)}
                />
                {requirements.length > 1 && (
                  <button
                    type="button"
                    onClick={() => removeRequirement(index)}
                    className="btn-remove"
                    title="Supprimer"
                  >
                    ✕
                  </button>
                )}
              </div>
            ))}

            <button
              type="button"
              onClick={addRequirement}
              className="btn-add-requirement"
            >
              + Ajouter une compétence
            </button>
          </div>

          <button type="submit" disabled={isSubmitting}>
            {isSubmitting ? "Publication en cours..." : "Publier l'offre"}
          </button>
        </form>
      </main>

      <Footer />
    </div>
  );
}

export default PublishOffer;