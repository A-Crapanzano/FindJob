import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

function AdminDashboard() {
    const navigate = useNavigate();
    const [activeTab, setActiveTab] = useState('users');
    const [users, setUsers] = useState([]);
    const [companies, setCompanies] = useState([]);
    const [offers, setOffers] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [showModal, setShowModal] = useState(false);
    const [modalType, setModalType] = useState('create');
    const [currentEntity, setCurrentEntity] = useState(null);
    const [formData, setFormData] = useState({});

    useEffect(() => {
        loadData();
    }, [activeTab]);

    const loadData = async () => {
        setLoading(true);
        setError('');
        try {
            let endpoint = '';
            if (activeTab === 'users') endpoint = '/api/admin/users';
            if (activeTab === 'companies') endpoint = '/api/admin/companies';
            if (activeTab === 'offers') endpoint = '/api/admin/job-offers';

            const res = await fetch(`http://localhost:8000${endpoint}`, {
                credentials: 'include',
            });

            if (!res.ok) throw new Error('Erreur de chargement');

            const data = await res.json();
            if (activeTab === 'users') setUsers(data.users || []);
            if (activeTab === 'companies') setCompanies(data.companies || []);
            if (activeTab === 'offers') setOffers(data.offers || []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleLogout = async () => {
        try {
            await fetch('http://localhost:8000/api/logout', {
                method: 'POST',
                credentials: 'include',
            });
            navigate('/login');
        } catch (err) {
            console.error(err);
        }
    };

    const openCreateModal = () => {
        setModalType('create');
        setCurrentEntity(null);
        setFormData({});
        setShowModal(true);
    };

    const openEditModal = (entity) => {
        setModalType('edit');
        setCurrentEntity(entity);
        setFormData(entity);
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
        setFormData({});
        setCurrentEntity(null);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');
        setLoading(true);

        try {
            let endpoint = '';
            let method = modalType === 'create' ? 'POST' : 'PATCH';

            if (activeTab === 'users') {
                endpoint = modalType === 'create'
                    ? '/api/admin/users'
                    : `/api/admin/users/${currentEntity.id}`;
            }
            if (activeTab === 'companies') {
                endpoint = modalType === 'create'
                    ? '/api/admin/companies'
                    : `/api/admin/companies/${currentEntity.id}`;
            }
            if (activeTab === 'offers') {
                endpoint = modalType === 'create'
                    ? '/api/admin/job-offers'
                    : `/api/admin/job-offers/${currentEntity.id}`;
            }

            const res = await fetch(`http://localhost:8000${endpoint}`, {
                method,
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(formData),
            });

            if (!res.ok) {
                const data = await res.json();
                throw new Error(data.error || 'Erreur lors de la sauvegarde');
            }

            setSuccess(modalType === 'create' ? 'Créé avec succès' : 'Mis à jour avec succès');
            closeModal();
            loadData();
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) return;

        setError('');
        setSuccess('');
        setLoading(true);

        try {
            let endpoint = '';
            if (activeTab === 'users') endpoint = `/api/admin/users/${id}`;
            if (activeTab === 'companies') endpoint = `/api/admin/companies/${id}`;
            if (activeTab === 'offers') endpoint = `/api/admin/job-offers/${id}`;

            const res = await fetch(`http://localhost:8000${endpoint}`, {
                method: 'DELETE',
                credentials: 'include',
            });

            if (!res.ok) throw new Error('Erreur lors de la suppression');

            setSuccess('Supprimé avec succès');
            loadData();
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const renderUserForm = () => (
        <>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Email</label>
                <input
                    type="email"
                    value={formData.email || ''}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                    required
                />
            </div>
            {modalType === 'create' && (
                <div style={{ marginBottom: '15px' }}>
                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Mot de passe</label>
                    <input
                        type="password"
                        value={formData.password || ''}
                        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                        style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                        required
                    />
                </div>
            )}
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Prénom</label>
                <input
                    type="text"
                    value={formData.firstname || ''}
                    onChange={(e) => setFormData({ ...formData, firstname: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                    required
                />
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Nom</label>
                <input
                    type="text"
                    value={formData.lastname || ''}
                    onChange={(e) => setFormData({ ...formData, lastname: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                    required
                />
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Statut</label>
                <select
                    value={formData.status || 'candidate'}
                    onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                >
                    <option value="candidate">Candidat</option>
                    <option value="recruiter">Recruteur</option>
                </select>
            </div>
        </>
    );

    const renderCompanyForm = () => (
        <>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Nom</label>
                <input
                    type="text"
                    value={formData.name || ''}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                    required
                />
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Description</label>
                <textarea
                    value={formData.description || ''}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px', minHeight: '100px' }}
                />
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Localisation</label>
                <input
                    type="text"
                    value={formData.location || ''}
                    onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                />
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Site web</label>
                <input
                    type="url"
                    value={formData.website || ''}
                    onChange={(e) => setFormData({ ...formData, website: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                />
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Taille</label>
                <select
                    value={formData.size || ''}
                    onChange={(e) => setFormData({ ...formData, size: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                >
                    <option value="">Sélectionner</option>
                    <option value="startup">Startup</option>
                    <option value="small">Petite</option>
                    <option value="medium">Moyenne</option>
                    <option value="large">Grande</option>
                    <option value="enterprise">Entreprise</option>
                </select>
            </div>
        </>
    );

    const renderOfferForm = () => (
        <>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Titre</label>
                <input
                    type="text"
                    value={formData.title || ''}
                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                    required
                />
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Description</label>
                <textarea
                    value={formData.description || ''}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px', minHeight: '100px' }}
                    required
                />
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Localisation</label>
                <input
                    type="text"
                    value={formData.location || ''}
                    onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                />
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Type de contrat</label>
                <select
                    value={formData.contractType || ''}
                    onChange={(e) => setFormData({ ...formData, contractType: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                >
                    <option value="">Sélectionner</option>
                    <option value="CDI">CDI</option>
                    <option value="CDD">CDD</option>
                    <option value="Stage">Stage</option>
                    <option value="Alternance">Alternance</option>
                </select>
            </div>
            <div style={{ marginBottom: '15px' }}>
                <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>Type de télétravail</label>
                <select
                    value={formData.remoteType || 'onsite'}
                    onChange={(e) => setFormData({ ...formData, remoteType: e.target.value })}
                    style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                >
                    <option value="onsite">Sur site</option>
                    <option value="remote">Télétravail</option>
                    <option value="hybrid">Hybride</option>
                </select>
            </div>
            {modalType === 'create' && (
                <>
                    <div style={{ marginBottom: '15px' }}>
                        <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>ID Utilisateur</label>
                        <input
                            type="number"
                            value={formData.userId || ''}
                            onChange={(e) => setFormData({ ...formData, userId: parseInt(e.target.value) })}
                            style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                            required
                        />
                    </div>
                    <div style={{ marginBottom: '15px' }}>
                        <label style={{ display: 'block', marginBottom: '5px', fontWeight: '500' }}>ID Entreprise</label>
                        <input
                            type="number"
                            value={formData.companyId || ''}
                            onChange={(e) => setFormData({ ...formData, companyId: parseInt(e.target.value) })}
                            style={{ width: '100%', padding: '8px', border: '1px solid #ddd', borderRadius: '4px' }}
                            required
                        />
                    </div>
                </>
            )}
        </>
    );

    return (
        <div style={{ minHeight: '100vh', background: '#fff' }}>
            <header style={{
                background: '#fff',
                borderBottom: '1px solid #e5e5e5',
                padding: '20px 40px',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center'
            }}>
                <h1 style={{ margin: 0, fontSize: '24px', fontWeight: '600' }}>Dashboard Admin</h1>
                <button
                    onClick={handleLogout}
                    style={{
                        padding: '8px 16px',
                        background: '#000000ad',
                        color: '#fff',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontSize: '14px'
                    }}
                >
                    Déconnexion
                </button>
            </header>

            <div style={{ display: 'flex', minHeight: 'calc(100vh - 80px)' }}>
                <aside style={{
                    width: '250px',
                    borderRight: '1px solid #e5e5e5',
                    padding: '20px'
                }}>
                    <nav>
                        <button
                            onClick={() => setActiveTab('users')}
                            style={{
                                width: '100%',
                                padding: '12px',
                                background: activeTab === 'users' ? '#f5f5f5' : 'transparent',
                                border: 'none',
                                borderRadius: '4px',
                                textAlign: 'left',
                                cursor: 'pointer',
                                marginBottom: '8px',
                                fontSize: '14px',
                                fontWeight: activeTab === 'users' ? '600' : '400'
                            }}
                        >
                            Utilisateurs
                        </button>
                        <button
                            onClick={() => setActiveTab('companies')}
                            style={{
                                width: '100%',
                                padding: '12px',
                                background: activeTab === 'companies' ? '#f5f5f5' : 'transparent',
                                border: 'none',
                                borderRadius: '4px',
                                textAlign: 'left',
                                cursor: 'pointer',
                                marginBottom: '8px',
                                fontSize: '14px',
                                fontWeight: activeTab === 'companies' ? '600' : '400'
                            }}
                        >
                            Entreprises
                        </button>
                        <button
                            onClick={() => setActiveTab('offers')}
                            style={{
                                width: '100%',
                                padding: '12px',
                                background: activeTab === 'offers' ? '#f5f5f5' : 'transparent',
                                border: 'none',
                                borderRadius: '4px',
                                textAlign: 'left',
                                cursor: 'pointer',
                                fontSize: '14px',
                                fontWeight: activeTab === 'offers' ? '600' : '400'
                            }}
                        >
                            Offres d'emploi
                        </button>
                    </nav>
                </aside>

                <main style={{ flex: 1, padding: '40px' }}>
                    {error && (
                        <div style={{
                            padding: '12px',
                            background: '#fee',
                            color: '#c00',
                            borderRadius: '4px',
                            marginBottom: '20px'
                        }}>
                            {error}
                        </div>
                    )}

                    {success && (
                        <div style={{
                            padding: '12px',
                            background: '#efe',
                            color: '#0a0',
                            borderRadius: '4px',
                            marginBottom: '20px'
                        }}>
                            {success}
                        </div>
                    )}

                    <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        marginBottom: '30px'
                    }}>
                        <h2 style={{ margin: 0, fontSize: '20px', fontWeight: '600' }}>
                            {activeTab === 'users' && 'Gestion des utilisateurs'}
                            {activeTab === 'companies' && 'Gestion des entreprises'}
                            {activeTab === 'offers' && 'Gestion des offres'}
                        </h2>
                        <button
                            onClick={openCreateModal}
                            style={{
                                padding: '10px 20px',
                                background: '#000000ad',
                                color: '#fff',
                                border: 'none',
                                borderRadius: '4px',
                                cursor: 'pointer',
                                fontSize: '14px',
                                fontWeight: '500'
                            }}
                        >
                            + Créer
                        </button>
                    </div>

                    {loading ? (
                        <div style={{ textAlign: 'center', padding: '40px' }}>Chargement...</div>
                    ) : (
                        <div style={{
                            border: '1px solid #e5e5e5',
                            borderRadius: '8px',
                            overflow: 'hidden'
                        }}>
                            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                                <thead>
                                    <tr style={{ background: '#f9f9f9', borderBottom: '1px solid #e5e5e5' }}>
                                        {activeTab === 'users' && (
                                            <>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>ID</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Email</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Nom</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Statut</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Actions</th>
                                            </>
                                        )}
                                        {activeTab === 'companies' && (
                                            <>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>ID</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Nom</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Localisation</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Taille</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Actions</th>
                                            </>
                                        )}
                                        {activeTab === 'offers' && (
                                            <>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>ID</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Titre</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Entreprise</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Localisation</th>
                                                <th style={{ padding: '12px', textAlign: 'left', fontWeight: '600', fontSize: '14px' }}>Actions</th>
                                            </>
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {activeTab === 'users' && users.map((user) => (
                                        <tr key={user.id} style={{ borderBottom: '1px solid #e5e5e5' }}>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{user.id}</td>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{user.email}</td>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{user.firstname} {user.lastname}</td>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{user.status}</td>
                                            <td style={{ padding: '12px' }}>
                                                <button
                                                    onClick={() => openEditModal(user)}
                                                    style={{
                                                        padding: '6px 12px',
                                                        background: '#f5f5f5',
                                                        border: '1px solid #e5e5e5',
                                                        borderRadius: '4px',
                                                        cursor: 'pointer',
                                                        fontSize: '13px',
                                                        marginRight: '8px'
                                                    }}
                                                >
                                                    Modifier
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(user.id)}
                                                    style={{
                                                        padding: '6px 12px',
                                                        background: '#fee',
                                                        color: '#c00',
                                                        border: '1px solid #fcc',
                                                        borderRadius: '4px',
                                                        cursor: 'pointer',
                                                        fontSize: '13px'
                                                    }}
                                                >
                                                    Supprimer
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                    {activeTab === 'companies' && companies.map((company) => (
                                        <tr key={company.id} style={{ borderBottom: '1px solid #e5e5e5' }}>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{company.id}</td>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{company.name}</td>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{company.location || '-'}</td>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{company.size || '-'}</td>
                                            <td style={{ padding: '12px' }}>
                                                <button
                                                    onClick={() => openEditModal(company)}
                                                    style={{
                                                        padding: '6px 12px',
                                                        background: '#f5f5f5',
                                                        border: '1px solid #e5e5e5',
                                                        borderRadius: '4px',
                                                        cursor: 'pointer',
                                                        fontSize: '13px',
                                                        marginRight: '8px'
                                                    }}
                                                >
                                                    Modifier
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(company.id)}
                                                    style={{
                                                        padding: '6px 12px',
                                                        background: '#fee',
                                                        color: '#c00',
                                                        border: '1px solid #fcc',
                                                        borderRadius: '4px',
                                                        cursor: 'pointer',
                                                        fontSize: '13px'
                                                    }}
                                                >
                                                    Supprimer
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                    {activeTab === 'offers' && offers.map((offer) => (
                                        <tr key={offer.id} style={{ borderBottom: '1px solid #e5e5e5' }}>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{offer.id}</td>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{offer.title}</td>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{offer.company?.name}</td>
                                            <td style={{ padding: '12px', fontSize: '14px' }}>{offer.location || '-'}</td>
                                            <td style={{ padding: '12px' }}>
                                                <button
                                                    onClick={() => openEditModal(offer)}
                                                    style={{
                                                        padding: '6px 12px',
                                                        background: '#f5f5f5',
                                                        border: '1px solid #e5e5e5',
                                                        borderRadius: '4px',
                                                        cursor: 'pointer',
                                                        fontSize: '13px',
                                                        marginRight: '8px'
                                                    }}
                                                >
                                                    Modifier
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(offer.id)}
                                                    style={{
                                                        padding: '6px 12px',
                                                        background: '#fee',
                                                        color: '#c00',
                                                        border: '1px solid #fcc',
                                                        borderRadius: '4px',
                                                        cursor: 'pointer',
                                                        fontSize: '13px'
                                                    }}
                                                >
                                                    Supprimer
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </main>
            </div>

            {showModal && (
                <div style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    background: 'rgba(0,0,0,0.5)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    zIndex: 1000
                }}>
                    <div style={{
                        background: '#fff',
                        borderRadius: '8px',
                        padding: '30px',
                        width: '100%',
                        maxWidth: '500px',
                        maxHeight: '80vh',
                        overflow: 'auto'
                    }}>
                        <h3 style={{ marginTop: 0, marginBottom: '20px', fontSize: '18px', fontWeight: '600' }}>
                            {modalType === 'create' ? 'Créer' : 'Modifier'}
                        </h3>
                        <form onSubmit={handleSubmit}>
                            {activeTab === 'users' && renderUserForm()}
                            {activeTab === 'companies' && renderCompanyForm()}
                            {activeTab === 'offers' && renderOfferForm()}

                            <div style={{ display: 'flex', gap: '10px', marginTop: '20px' }}>
                                <button
                                    type="submit"
                                    disabled={loading}
                                    style={{
                                        flex: 1,
                                        padding: '10px',
                                        background: '#000',
                                        color: '#fff',
                                        border: 'none',
                                        borderRadius: '4px',
                                        cursor: loading ? 'not-allowed' : 'pointer',
                                        fontSize: '14px',
                                        fontWeight: '500'
                                    }}
                                >
                                    {loading ? 'Chargement...' : 'Enregistrer'}
                                </button>
                                <button
                                    type="button"
                                    onClick={closeModal}
                                    disabled={loading}
                                    style={{
                                        flex: 1,
                                        padding: '10px',
                                        background: '#f5f5f5',
                                        color: '#000',
                                        border: '1px solid #e5e5e5',
                                        borderRadius: '4px',
                                        cursor: loading ? 'not-allowed' : 'pointer',
                                        fontSize: '14px'
                                    }}
                                >
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

export default AdminDashboard;