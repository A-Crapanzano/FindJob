import { useParams, useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";
import { useAuthStore } from "@/store/authStore";
import Header from "@/components/header.jsx";
import Footer from "@/components/footer.jsx";
import {
  Briefcase,
  Building2,
  MapPin,
  FileText,
  Wifi,
  Euro,
  CheckCircle2,
  Calendar
} from "lucide-react";
import "../styles/OfferPage.css";

function OfferPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuthStore();
  const [job, setJob] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`http://localhost:8000/api/job-offers/${id}`)
      .then((res) => res.json())
      .then((data) => {
        setJob(data);
        setLoading(false);
      })
      .catch((err) => {
        console.error("Erreur:", err);
        setLoading(false);
      });
  }, [id]);

  const handleApply = () => {
    if (!user) {
      navigate("/login");
      return;
    }
    navigate(`/postuler?jobId=${id}`);
  };

  return (
    <div className="page-container">
      <Header />

      <main className="main-content">
        {loading && <p>Chargement...</p>}

        {!loading && job && (
          <section className="card-content">
            <div className="card-text">
              <h2><Briefcase size={24} className="icon" /> {job.title}</h2>
              <h3><Building2 size={24} className="icon" /> {job.company.name}</h3>
              <p><MapPin size={20} className="icon" /> {job.location}</p>
              <p><FileText size={20} className="icon" /> {job.contractType}</p>
              <p><Wifi size={20} className="icon" /> {job.remoteType}</p>

              {job.salaryMin && job.salaryMax && (
                <p><Euro size={20} className="icon" /> {job.salaryMin}€ - {job.salaryMax}€ /an</p>
              )}

              <h4>Description</h4>
              <p>{job.description}</p>

              {job.requirements && Array.isArray(job.requirements) && job.requirements.length > 0 && (
                <>
                  <h4>Compétences requises</h4>
                  <ul className="job-list">
                    {job.requirements.map((requirement, index) => (
                      <li key={index}>
                        <CheckCircle2 size={16} className="icon" /> {requirement}
                      </li>
                    ))}
                  </ul>
                </>
              )}

              <button className="button-offer" onClick={handleApply}>
                <FileText size={20} style={{ marginRight: "8px" }} />
                Postuler
              </button>

              <div className="job-card-footer">
                <span className="tag">
                  <Calendar size={16} style={{ marginRight: "6px" }} />
                  Publiée le {new Date(job.createdAt).toLocaleDateString()}
                </span>
              </div>
            </div>
          </section>
        )}
      </main>

      <Footer />
    </div>
  );
}

export default OfferPage;