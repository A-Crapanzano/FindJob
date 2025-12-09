import { create } from "zustand";

export const useAuthStore = create((set) => ({
  user: null,
  isAuthenticated: false,
  loading: true,

  checkAuth: async () => {
    try {
      const response = await fetch("http://localhost:8000/api/profile", {
        method: "GET",
        credentials: "include",
      });

      if (response.ok) {
        const data = await response.json();
        set({ user: data, isAuthenticated: true, loading: false });
      } else {
        set({ user: null, isAuthenticated: false, loading: false });
      }
    } catch (error) {
      console.error("Erreur auth:", error);
      set({ user: null, isAuthenticated: false, loading: false });
    }
  },

  logout: () => {
    set({ user: null, isAuthenticated: false, loading: false });
  },

  isCandidate: () => {
    const state = useAuthStore.getState();
    return state.user?.status === "candidate";
  },

  isRecruiter: () => {
    const state = useAuthStore.getState();
    return state.user?.status === "recruiter";
  },

  setUser: (userData) => {
    set({ user: userData });
  },
}));
