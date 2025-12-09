import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import "../styles/MyApplications.css";
import Header from "@/components/header.jsx";
import Footer from "@/components/footer.jsx";

function MyApplications() {
  const [applications, setApplications] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("http://localhost:8000/api/applications", {
      credentials: "include",
    })
      .then((res) => res.json())
      .then((data) => {
        setApplications(data.applications || []);
        setLoading(false);
      })
      .catch((err) => {
        console.error("Erreur:", err);
        setLoading(false);
      });
  }, []);

  return (
    <div className="page-container">
      <Header />

      <main className="main-content">
        <h1>Mes candidatures</h1>
        <p>Retrouvez ici toutes les offres auxquelles vous avez postulé.</p>

        {loading && <p>Chargement...</p>}

        {!loading && applications.length === 0 && (
          <p>Vous n'avez pas encore de candidatures.</p>
        )}

        <section className="applications-list">
          {applications.map((app) => (
            <div key={app.id} className="application-card">
              <h2>{app.jobOffer.title}</h2>
              <p><strong>Entreprise :</strong> {app.company.name}</p>
              <p><strong>Localisation :</strong> {app.jobOffer.location}</p>
              <p><strong>Type de contrat :</strong> {app.jobOffer.contractType}</p>
              <p><strong>Mode :</strong> {app.jobOffer.remoteType}</p>
              <p><strong>Statut :</strong> {app.status}</p>
              <p><strong>Postulé le :</strong> {new Date(app.appliedAt).toLocaleDateString()}</p>

              <Link to={`/jobs/${app.jobOffer.id}`} className="button">
                Voir l'offre
              </Link>
            </div>
          ))}
        </section>
      </main>

      <Footer />
    </div>
  );
}

export default MyApplications;