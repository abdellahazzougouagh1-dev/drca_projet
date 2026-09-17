import { useEffect } from 'react';

/**
 * Custom Hook: useClickOutside
 * Détecte les clics en dehors d'un élément référencé et exécute un callback.
 * 
 * @param {React.RefObject} ref - Référence vers l'élément DOM surveillé
 * @param {Function} handler - Fonction exécutée lors d'un clic extérieur
 * @param {boolean} active - Si le listener doit être actif ou non
 */
export function useClickOutside(ref, handler, active = true) {
  useEffect(() => {
    if (!active) return;

    const listener = (event) => {
      // Ne rien faire si on clique à l'intérieur du conteneur
      if (!ref.current || ref.current.contains(event.target)) {
        return;
      }
      handler(event);
    };

    document.addEventListener('mousedown', listener);
    document.addEventListener('touchstart', listener);

    return () => {
      document.removeEventListener('mousedown', listener);
      document.removeEventListener('touchstart', listener);
    };
  }, [ref, handler, active]);
}

export default useClickOutside;
