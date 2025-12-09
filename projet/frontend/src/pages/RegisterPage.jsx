import { useNavigate } from "react-router-dom";
import "../styles/RegisterPage.css";
import Footer from "@/components/footer.jsx";
import Header from "@/components/header.jsx";

function RegisterPage() {
  const navigate = useNavigate();
  const handleSubmit = async (event) => {
    event.preventDefault();

    const data = {
      email: event.target.email.value,
      password: event.target.password.value,
      firstname: event.target.firstname.value,
      lastname: event.target.lastname.value,
      city: event.target.city.value,
      zipcode: event.target.zipcode.value,
      status: event.target.status.value,
    };

    try {
      const response = await fetch("http://localhost:8000/api/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });

      const text = await response.text();

      if (!response.ok) {
        throw new Error(`Erreur serveur (${response.status}): ${text}`);
      }

      const result = JSON.parse(text);
      console.log(result);

      navigate("/login");
    } catch (error) {
      console.error("Erreur dans handleSubmit:", error);
    }
  };

  return (
    <div className="page-container">
      <Header />

      <main className="main-content">
        <h1>Inscription</h1>
        <form className="register-form" onSubmit={handleSubmit}>
          <input type="text" name="firstname" placeholder="Prénom" required />
          <input type="text" name="lastname" placeholder="Nom" required />
          <input type="text" name="city" placeholder="Ville" required />
          <input
            type="text"
            name="zipcode"
            placeholder="Code Postal"
            required
          />
          <select name="status" required>
            <option value="candidate">Candidat</option>
            <option value="recruiter">Recruteur</option>
          </select>
          <input type="email" name="email" placeholder="Email" required />
          <input
            type="password"
            name="password"
            placeholder="Mot de passe"
            required
          />
          <button type="submit">S'inscrire</button>
        </form>
      </main>

      <Footer />
    </div>
  );
}

export default RegisterPage;
