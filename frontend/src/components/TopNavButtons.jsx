import React from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { ShieldCheck, FileText, Receipt, Handshake } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

/**
 * TopNavButtons
 * Displays the 3 switch buttons for the other main sections:
 * - /directeur (Directeur)
 * - /dashboard (Appels d'offres)
 * - /bons-commande (Bons de commande)
 * - /convention (Conventions)
 */
export default function TopNavButtons({ current }) {
  const navigate = useNavigate();
  const location = useLocation();
  const { currentUser, token } = useAuth();

  // Détection automatique de la page actuelle si non passée en prop
  let activeKey = current;
  if (!activeKey) {
    const path = location.pathname.toLowerCase();
    if (path.startsWith('/directeur')) {
      activeKey = 'directeur';
    } else if (path.startsWith('/convention')) {
      activeKey = 'convention';
    } else if (path.startsWith('/bon')) {
      activeKey = 'bons-commande';
    } else {
      activeKey = 'dashboard';
    }
  }

  const handleNavigate = (target) => {
    // Si redirection vers Directeur
    if (target.key === 'directeur') {
      if (currentUser?.role === 'directeur') {
        navigate('/directeur');
      } else {
        navigate('/login', {
          state: {
            redirect: '/directeur',
            requiredRole: 'directeur',
            title: 'Espace Directeur',
          },
        });
      }
      return;
    }

    // Pour les autres sections
    if (!token) {
      navigate('/login', { state: { redirect: target.route, title: target.label } });
    } else {
      navigate(target.route);
    }
  };

  const sections = [
    {
      key: 'directeur',
      label: 'Directeur',
      route: '/directeur',
      title: "Accéder à l'Espace Directeur",
      icon: ShieldCheck,
      style: 'bg-[#101B2D] hover:bg-[#172B46] text-white border border-[#101B2D] shadow-xs',
      iconColor: 'text-amber-400',
    },
    {
      key: 'dashboard',
      label: "Appels d'offres",
      route: '/dashboard',
      title: "Accéder au Cycle des Appels d'offres",
      icon: FileText,
      style: 'bg-white hover:bg-slate-100 text-[#172033] border border-slate-200 shadow-xs',
      iconColor: 'text-blue-600',
    },
    {
      key: 'bons-commande',
      label: 'Bons de commande',
      route: '/bons-commande',
      title: 'Accéder aux Bons de commande',
      icon: Receipt,
      style: 'bg-white hover:bg-slate-100 text-[#172033] border border-slate-200 shadow-xs',
      iconColor: 'text-[#087F5B]',
    },
    {
      key: 'convention',
      label: 'Conventions',
      route: '/convention',
      title: 'Accéder aux Conventions',
      icon: Handshake,
      style: 'bg-[#E7F5EF] hover:bg-[#d8efe5] text-[#087F5B] border border-[#087F5B]/30 shadow-xs',
      iconColor: 'text-[#087F5B]',
    },
  ];

  // Afficher les 3 boutons correspondant aux 3 autres interfaces
  const otherSections = sections.filter((s) => s.key !== activeKey);

  return (
    <div className="flex flex-wrap items-center gap-1.5 sm:gap-2">
      {otherSections.map((btn) => {
        const Icon = btn.icon;
        return (
          <button
            key={btn.key}
            type="button"
            onClick={() => handleNavigate(btn)}
            className={`inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 text-[11px] sm:text-xs font-bold rounded-xl transition duration-150 active:scale-98 cursor-pointer whitespace-nowrap ${btn.style}`}
            title={btn.title}
          >
            <Icon size={14} className={`shrink-0 sm:w-[15px] sm:h-[15px] ${btn.iconColor}`} />
            <span>{btn.label}</span>
          </button>
        );
      })}
    </div>
  );
}
