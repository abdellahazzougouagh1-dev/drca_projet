import React from 'react';

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('ErrorBoundary caught an error:', error, errorInfo);
    this.setState({ errorInfo });
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-screen bg-slate-100 flex items-center justify-center p-6">
          <div className="bg-white rounded-2xl p-8 max-w-xl w-full shadow-lg border border-red-200">
            <div className="flex items-center gap-3 text-red-600 mb-4">
              <svg className="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" strokeWidth="2" />
                <line x1="12" y1="8" x2="12" y2="12" strokeWidth="2" strokeLinecap="round" />
                <line x1="12" y1="16" x2="12.01" y2="16" strokeWidth="2" strokeLinecap="round" />
              </svg>
              <h1 className="text-xl font-bold">Une erreur inattendue est survenue</h1>
            </div>
            <p className="text-slate-600 text-sm mb-4">
              L&apos;application a rencontré une difficulté lors de l&apos;affichage de cette page.
            </p>
            {this.state.error && (
              <div className="p-3 bg-red-50 text-red-800 rounded-xl text-xs font-mono mb-6 overflow-x-auto border border-red-100">
                {this.state.error.toString()}
              </div>
            )}
            <div className="flex gap-3">
              <button
                onClick={() => {
                  this.setState({ hasError: false, error: null, errorInfo: null });
                  window.location.reload();
                }}
                className="px-5 py-2.5 bg-[#1e40af] text-white font-bold rounded-xl text-sm hover:bg-[#1e3a8a] transition shadow-sm"
              >
                Actualiser la page
              </button>
              <button
                onClick={() => {
                  this.setState({ hasError: false, error: null, errorInfo: null });
                  window.location.href = '/dashboard';
                }}
                className="px-5 py-2.5 bg-slate-200 text-slate-800 font-bold rounded-xl text-sm hover:bg-slate-300 transition"
              >
                Retour au Tableau de Bord
              </button>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
