import { useEffect, useState } from "react";
import { useParams, Link } from "react-router-dom";
import "../styles/JobApplications.css";
import Header from "@/components/header.jsx";
import Footer from "@/components/footer.jsx";

function JobApplications() {
    const { id } = useParams();
    const [applications, setApplications] = useState([]);
    const [jobOffer, setJobOffer] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        fetch(`http://localhost:8000/api/job-offers/${id}/applications`, {
            credentials: "include",
        })
            .then((res) => {
                if (!res.ok) {
                    throw new Error("Erreur lors du chargement des candidatures");
                }
                return res.json();
            })
            .then((data) => {
                setApplications(data.applications || []);
                setJobOffer(data.jobOffer);
                setLoading(false);
            })
            .catch((err) => {
                console.error("Erreur:", err);
                setError(err.message);
                setLoading(false);
            });
    }, [id]);

    const updateStatus = async (applicationId, newStatus) => {
        try {
            const response = await fetch(
                `http://localhost:8000/api/applications/${applicationId}/status`,
                {
                    method: "PATCH",
                    headers: { "Content-Type": "application/json" },
                    credentials: "include",
                    body: JSON.stringify({ status: newStatus }),
                }
            );

            if (!response.ok) {
                throw new Error("Erreur lors de la mise à jour du statut");
            }

            window.location.reload();
        } catch (err) {
            console.error("Erreur:", err);
            alert("Erreur lors de la mise à jour du statut");
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            pending: { label: "En attente", class: "status-pending" },
            accepted: { label: "Acceptée", class: "status-accepted" },
            rejected: { label: "Refusée", class: "status-rejected" },
        };
        return badges[status] || badges.pending;
    };

    return (
        <div className="page-container">
            <Header />

            <main className="main-content">
                {jobOffer && (
                    <>
                        <h1>Candidatures pour : {jobOffer.title}</h1>
                        <p>
                            {applications.length} candidature{applications.length > 1 ? "s" : ""}
                        </p>
                    </>
                )}

                {loading && <p>Chargement...</p>}

                {error && <p className="error-message">{error}</p>}

                {!loading && !error && applications.length === 0 && (
                    <p>Aucune candidature pour cette offre.</p>
                )}

                <section className="applications-list">
                    {applications.map((app) => {
                        const statusInfo = getStatusBadge(app.status);
                        return (
                            <div key={app.id} className="application-card">
                                <div className="application-header">
                                    <h2>
                                        {app.candidate.firstname} {app.candidate.lastname}
                                    </h2>
                                    <span className={`status-badge ${statusInfo.class}`}>
                                        {statusInfo.label}
                                    </span>
                                </div>

                                <p>
                                    <strong>Email :</strong> {app.candidate.email}
                                </p>
                                <p>
                                    <strong>Localisation :</strong> {app.candidate.city}{" "}
                                    {app.candidate.zipcode}
                                </p>
                                <p>
                                    <strong>Postulé le :</strong>{" "}
                                    {new Date(app.appliedAt).toLocaleDateString()}
                                </p>

                                {app.message && (
                                    <div className="application-message">
                                        <strong>Message :</strong>
                                        <p>{app.message}</p>
                                    </div>
                                )}

                                <div className="application-actions">
                                    {app.resumeUrl && (
                                        <a
                                            href={app.resumeUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="button button-cv"
                                        >
                                            Voir le CV
                                        </a>
                                    )}

                                    {app.status === "pending" && (
                                        <>
                                            <button
                                                className="button button-accept"
                                                onClick={() => updateStatus(app.id, "accepted")}
                                            >
                                                Accepter
                                            </button>
                                            <button
                                                className="button button-reject"
                                                onClick={() => updateStatus(app.id, "rejected")}
                                            >
                                                Refuser
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </section>

                {jobOffer && (
                    <div style={{ marginTop: "30px" }}>
                        <Link to="/mes-offres" className="button button-back">
                            ← Retour à mes offres
                        </Link>
                    </div>
                )}
            </main>

            <Footer />
        </div>
    );
}

export default JobApplications;