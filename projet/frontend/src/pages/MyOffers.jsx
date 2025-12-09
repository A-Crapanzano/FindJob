import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import "../styles/MyOffers.css";
import Header from "@/components/header.jsx";
import Footer from "@/components/footer.jsx";

function MyOffers() {
    const [offers, setOffers] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch("http://localhost:8000/api/my-job-offers", {
            credentials: "include",
        })
            .then((res) => res.json())
            .then((data) => {
                setOffers(data.offers || []);
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
                <h1>Mes offres d'emploi</h1>
                <p>Retrouvez ici toutes les offres que vous avez publiées.</p>

                {loading && <p>Chargement...</p>}

                {!loading && offers.length === 0 && (
                    <div>
                        <p>Vous n'avez pas encore publié d'offres.</p>
                        <Link to="/publier-votre-offre" className="button">
                            Publier une offre
                        </Link>
                    </div>
                )}

                <section className="offers-list">
                    {offers.map((offer) => (
                        <div key={offer.id} className="offer-card">
                            <h2>{offer.title}</h2>
                            <p><strong>Entreprise :</strong> {offer.company.name}</p>
                            <p><strong>Localisation :</strong> {offer.location || "Non spécifiée"}</p>
                            <p><strong>Type de contrat :</strong> {offer.contractType || "Non spécifié"}</p>
                            <p><strong>Mode :</strong> {offer.remoteType}</p>
                            {offer.salaryMin && offer.salaryMax && (
                                <p><strong>Salaire :</strong> {offer.salaryMin}€ - {offer.salaryMax}€</p>
                            )}
                            <p><strong>Candidatures :</strong> {offer.applicationsCount}</p>
                            <p><strong>Publiée le :</strong> {new Date(offer.createdAt).toLocaleDateString()}</p>

                            <div className="offer-card-footer">
                                <Link to={`/jobs/${offer.id}`} className="button">
                                    Voir l'offre
                                </Link>
                                <Link to={`/jobs/${offer.id}/applications`} className="button button-secondary">
                                    Voir les candidatures
                                </Link>
                            </div>
                        </div>
                    ))}
                </section>
            </main>

            <Footer />
        </div>
    );
}

export default MyOffers;