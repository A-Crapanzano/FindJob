import {
  Building2,
  MapPin,
  Users,
  Globe,
  Mail,
  Phone,
  Briefcase,
} from "lucide-react";
import { useParams, Link } from "react-router-dom";
import { useEffect, useState } from "react";
import "../styles/CompanyPage.css";
import Footer from "@/components/footer.jsx";
import Header from "@/components/header.jsx";


function CompanyPage() {
  const { id } = useParams();
  const [company, setCompany] = useState(null);
  const [offers, setOffers] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchCompanyAndOffers = async () => {
      try {
        const [companyRes, offersRes] = await Promise.all([
          fetch(`http://localhost:8000/api/companies/${id}`),
          fetch(`http://localhost:8000/api/companies/${id}/jobs`),
        ]);

        if (!companyRes.ok || !offersRes.ok) throw new Error("Erreur serveur");

        const companyData = await companyRes.json();
        const offersData = await offersRes.json();

        setCompany(companyData);
        setOffers(offersData.data || []);
      } catch (err) {
        console.error("Erreur:", err);
      } finally {
        setLoading(false);
      }
    };

    fetchCompanyAndOffers();
  }, [id]);

  return (
    <div className="page-container">
      <Header />

      <main className="main-content">
        {loading && <p>Chargement...</p>}

        {!loading && company && (
          <>
            <div className="company-header">
              <div className="company-logo-container">
                <img
                  src={company.logoUrl || "/assets/images/company-logo.png"}
                  alt="Logo entreprise"
                  className="company-logo"
                />
              </div>
              <div className="company-title">
                <h1>{company.name}</h1>
                <p className="company-tagline">
                  {company.tagline || "Entreprise innovante"}
                </p>
              </div>
            </div>

            <section className="card-content">
              <div className="card-text">
                <h2>
                  <Building2 size={28} className="icon" /> À propos de
                  l'entreprise
                </h2>
                <p>{company.description || "Description non disponible."}</p>
              </div>
            </section>

            <section className="card-content">
              <div className="card-text">
                <h2>
                  <Users size={28} className="icon" /> Informations clés
                </h2>
                <div className="info-grid">
                  <div className="info-item">
                    <MapPin size={20} className="icon-small" />
                    <div>
                      <h5>Siège social</h5>
                      <p>{company.location || "Non renseigné"}</p>
                    </div>
                  </div>
                  <div className="info-item">
                    <Users size={20} className="icon-small" />
                    <div>
                      <h5>Effectif</h5>
                      <p>{company.size || "Non renseigné"}</p>
                    </div>
                  </div>
                  <div className="info-item">
                    <Briefcase size={20} className="icon-small" />
                    <div>
                      <h5>Secteur</h5>
                      <p>{company.industry || "Non renseigné"}</p>
                    </div>
                  </div>
                  <div className="info-item">
                    <Globe size={20} className="icon-small" />
                    <div>
                      <h5>Site web</h5>
                      <p>
                        <a
                          href={company.website}
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          {company.website || "Non disponible"}
                        </a>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <section className="card-content">
              <div className="card-text">
                <h2>
                  <Briefcase size={28} className="icon" /> Offres d'emploi
                </h2>
                <p className="jobs-intro">
                  Découvrez nos opportunités et rejoignez nos équipes !
                </p>

                {offers.length > 0 ? (
                  offers.map((offer) => (
                    <div key={offer.id} className="job-item">
                      <h3>{offer.title}</h3>
                      <p>
                        <MapPin size={16} className="icon-inline" />
                        {offer.location} • {offer.contractType} •{" "}
                        {offer.remoteType}
                      </p>
                      <Link to={`/jobs/${offer.id}`} className="button-offer">
                        Voir l'offre
                      </Link>
                    </div>
                  ))
                ) : (
                  <p>Aucune offre disponible pour le moment.</p>
                )}

                <button className="button-all-jobs">
                  Voir toutes les offres
                </button>
              </div>
            </section>

            <section className="card-content">
              <div className="card-text">
                <h2>
                  <Phone size={28} className="icon" /> Nous contacter
                </h2>
                <div className="contact-info">
                  <p>
                    <Mail size={20} className="icon-inline" />{" "}
                    {company.email || "recrutement@entreprise.com"}
                  </p>
                  <p>
                    <Phone size={20} className="icon-inline" />{" "}
                    {company.phone || "+33 1 44 44 22 22"}
                  </p>
                  <p>
                    <MapPin size={20} className="icon-inline" />{" "}
                    {company.address || "Adresse non renseignée"}
                  </p>
                </div>
              </div>
            </section>
          </>
        )}

        {!loading && !company && (
          <p className="no-results">Entreprise introuvable.</p>
        )}
      </main>

      <Footer />
    </div>
  );
}

export default CompanyPage;