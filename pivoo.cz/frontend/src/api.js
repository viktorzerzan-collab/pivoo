// frontend/src/api.js

const BASE_URL = '/backend/api';

/**
 * Univerzální funkce pro volání API
 * @param {string} endpoint - např. '/beers.php'
 * @param {object} options - options pro fetch (method, body, atd.)
 */
export async function apiFetch(endpoint, options = {}) {
  const token = localStorage.getItem('pivoo_token');
  
  // Připravíme základní hlavičky
  const headers = {
    ...options.headers,
  };

  // Pokud máme token, přidáme ho (pokud už tam není)
  if (token && !headers['Authorization']) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // Pokud posíláme JSON (a ne FormData), nastavíme Content-Type
  if (options.body && !(options.body instanceof FormData) && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(`${BASE_URL}${endpoint}`, {
    cache: 'no-store', // MAGIE: Zabráníme prohlížeči cachovat data z API
    ...options,
    headers
  });

  if (!response.ok) {
    if (response.status === 401) {
      // Zde by se dalo pořešit automatické odhlášení při expiračním tokenu
      console.error("Uživatel není autorizován.");
    }
    throw new Error(`API error: ${response.status}`);
  }

  return response.json();
}

export default BASE_URL;