import { useEffect, useRef, useState } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function RegistreScroll({ children, className = '' }) {
  const viewport = useRef(null);
  const content = useRef(null);
  const [scroll, setScroll] = useState({ position: 0, max: 0 });

  useEffect(() => {
    const node = viewport.current;
    const update = () => setScroll({ position: node.scrollLeft, max: Math.max(0, node.scrollWidth - node.clientWidth) });
    const observer = new ResizeObserver(update);
    observer.observe(node);
    observer.observe(content.current);
    node.addEventListener('scroll', update, { passive: true });
    update();
    return () => {
      observer.disconnect();
      node.removeEventListener('scroll', update);
    };
  }, []);

  const move = (value) => { viewport.current.scrollLeft = value; };
  return (
    <div className="min-w-0">
      <div ref={viewport} className={`overflow-x-auto ${className}`}>
        <div ref={content} className="min-w-full w-max">{children}</div>
      </div>
      <div className="flex items-center gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3" role="group" aria-label="Défilement horizontal du registre">
        <button type="button" aria-label="Défiler vers la gauche" disabled={scroll.position <= 0}
          onClick={() => move(Math.max(0, scroll.position - viewport.current.clientWidth * 0.6))}
          className="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-emerald-50 disabled:opacity-30">
          <ChevronLeft size={18} />
        </button>
        <input type="range" min="0" max={scroll.max} step="1" value={Math.min(scroll.position, scroll.max)}
          disabled={scroll.max === 0} onChange={(event) => move(Number(event.target.value))}
          aria-label="Position horizontale du registre"
          aria-valuetext={`${scroll.max ? Math.round(scroll.position / scroll.max * 100) : 0} %`}
          className="h-5 min-w-0 flex-1 cursor-pointer accent-emerald-700 disabled:opacity-40" />
        <button type="button" aria-label="Défiler vers la droite" disabled={scroll.position >= scroll.max - 1}
          onClick={() => move(Math.min(scroll.max, scroll.position + viewport.current.clientWidth * 0.6))}
          className="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-emerald-50 disabled:opacity-30">
          <ChevronRight size={18} />
        </button>
      </div>
    </div>
  );
}
