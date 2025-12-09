import { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { Briefcase, Building2, MapPin, FileText, Wifi, Euro, CheckCircle2, Calendar } from 'lucide-react';
import "../styles/HomePage.css";
import Footer from '@/components/footer.jsx';
import Header from '@/components/header.jsx';

function HomePage() {
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [pagination, setPagination] = useState({
    total: 0,
    page: 1,
    limit: 2,
    totalPages: 0
  });
  const [filters, setFilters] = useState({
    search: "",
    location: "",
    contract: "",
    remote: "",
    salaryMin: "",
    salaryMax: ""
  });

  useEffect(() => {
    fetchJobs();
  }, [pagination.page]);

  const fetchJobs = async () => {
    try {
      setLoading(true);

      const params = new URLSearchParams();
      params.append("page", pagination.page);
      params.append("limit", pagination.limit);
      if (filters.search) params.append("search", filters.search);
      if (filters.location) params.append("location", filters.location);
      if (filters.contract) params.append("contract", filters.contract);
      if (filters.remote) params.append("remote", filters.remote);
      if (filters.salaryMin) params.append("salaryMin", filters.salaryMin);
      if (filters.salaryMax) params.append("salaryMax", filters.salaryMax);

      const response = await fetch(`http://localhost:8000/api/job-offers?${params}`);
      const data = await response.json();

      setJobs(data.data);
      setPagination(prev => ({
        ...prev,
        total: data.pagination.total,
        totalPages: data.pagination.totalPages
      }));
    } catch (error) {
      console.error("Erreur:", error);
    } finally {
      setLoading(false);
    }
  };

  const searchJobs = (e) => {
    e.preventDefault();
    setPagination(prev => ({ ...prev, page: 1 }));
    fetchJobs();
  };

  const goToPage = (page) => {
    setPagination(prev => ({ ...prev, page }));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <div className="page-container">
      <Header />
      <main className="main-content">
        <h1>Bienvenue sur FindJob</h1>
        <p>Le site de petites annonces entre recruteurs et candidats.</p>

        <form className="search-form" onSubmit={searchJobs}>
          <input
            type="text"
            placeholder="Rechercher"
            value={filters.search}
            onChange={(e) => setFilters({ ...filters, search: e.target.value })}
          />

          <input
            type="text"
            placeholder="Ville"
            value={filters.location}
            onChange={(e) => setFilters({ ...filters, location: e.target.value })}
          />

          <select
            value={filters.contract}
            onChange={(e) => setFilters({ ...filters, contract: e.target.value })}
          >
            <option value="">Tous les contrats</option>
            <option value="CDI">CDI</option>
            <option value="CDD">CDD</option>
            <option value="Stage">Stage</option>
            <option value="Alternance">Alternance</option>
          </select>

          <select
            value={filters.remote}
            onChange={(e) => setFilters({ ...filters, remote: e.target.value })}
          >
            <option value="">Tous les modes</option>
            <option value="onsite">Sur site</option>
            <option value="remote">Remote</option>
            <option value="hybrid">Hybride</option>
          </select>

          <input
            type="number"
            placeholder="Salaire min"
            value={filters.salaryMin}
            onChange={(e) => setFilters({ ...filters, salaryMin: e.target.value })}
          />

          <input
            type="number"
            placeholder="Salaire max"
            value={filters.salaryMax}
            onChange={(e) => setFilters({ ...filters, salaryMax: e.target.value })}
          />

          <button type="submit">Rechercher</button>
        </form>
      </main>

      {!loading && jobs.map((job) => (
        <section key={job.id} className="card-content">
          <div className="card-text">
            <h2>{job.title}</h2>
            <p><strong><Building2 size={16} className="icon-inline" />{job.companyName}</strong></p>

            <p>{job.description.substring(0, 200)}...</p>

            {job.location && <p><MapPin size={16} className="icon-inline" />{job.location}</p>}
            {job.contractType && <p><Briefcase size={16} className="icon-inline" />{job.contractType}</p>}
            <p><Wifi size={16} className="icon-inline" />{job.remoteType}</p>

            <Link to={`/jobs/${job.id}`} className="button">
              Voir l'offre
            </Link>
          </div>
        </section>
      ))}

      {!loading && pagination.totalPages > 1 && (
        <div className="pagination">
          <button
            onClick={() => goToPage(pagination.page - 1)}
            disabled={pagination.page === 1}
            className="pagination-btn"
          >
            ← Précédent
          </button>

          <div className="pagination-pages">
            {[...Array(pagination.totalPages)].map((_, index) => {
              const pageNum = index + 1;
              if (
                pageNum === 1 ||
                pageNum === pagination.totalPages ||
                (pageNum >= pagination.page - 2 && pageNum <= pagination.page + 2)
              ) {
                return (
                  <button
                    key={pageNum}
                    onClick={() => goToPage(pageNum)}
                    className={`pagination-page ${pagination.page === pageNum ? 'active' : ''}`}
                  >
                    {pageNum}
                  </button>
                );
              } else if (
                pageNum === pagination.page - 3 ||
                pageNum === pagination.page + 3
              ) {
                return <span key={pageNum}>...</span>;
              }
              return null;
            })}
          </div>

          <button
            onClick={() => goToPage(pagination.page + 1)}
            disabled={pagination.page === pagination.totalPages}
            className="pagination-btn"
          >
            Suivant →
          </button>
        </div>
      )}

      <Footer />
    </div>
  );
}

export default HomePage;