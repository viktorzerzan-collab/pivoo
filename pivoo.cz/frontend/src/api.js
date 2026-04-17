// frontend/src/api.js

const BASE_URL = '/backend/api';
// Výchozí časový limit pro API požadavky (10 vteřin)
const TIMEOUT_MS = 10000;

/**
 * Univerzální funkce pro volání API s podporou Timeoutu
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

  // Založení AbortControlleru pro řešení timeoutu
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

  try {
    const response = await fetch(`${BASE_URL}${endpoint}`, {
      cache: 'no-store', // Zabráníme prohlížeči cachovat data z API
      ...options,
      headers,
      signal: controller.signal
    });

    clearTimeout(timeoutId); // Požadavek prošel, zrušíme časovač

    if (!response.ok) {
      // Zpracování chyb 401 (Neautorizováno) a 403 (Zakázáno / BAN)
      if (response.status === 401 || response.status === 403) {
        console.error(response.status === 403 ? "Přístup odepřen (možný BAN)." : "Uživatel není autorizován.");
        
        // Okamžité smazání dat o přihlášení
        localStorage.removeItem('pivoo_token');
        localStorage.removeItem('pivoo_user');
        
        // Přesměrování na přihlašovací stránku, pokud už tam nejsme
        if (window.location.pathname !== '/') {
          window.location.href = '/';
        }
      }
      
      // Pokusíme se vyčíst chybovou zprávu ze serveru
      let errorData = null;
      try {
        errorData = await response.json();
      } catch (e) {
        // Ignorujeme, pokud odpověď není validní JSON
      }
      
      if (errorData && errorData.message) {
        // Vyhodíme chybu se zprávou od serveru (např. "Váš účet byl zablokován.")
        throw new Error(errorData.message);
      } else {
        throw new Error(`API error: ${response.status}`);
      }
    }

    return await response.json();

  } catch (err) {
    // Pokud byl požadavek zrušen kvůli timeoutu
    if (err.name === 'AbortError') {
      throw new Error('Vypršel časový limit spojení se serverem. Zkontrolujte připojení k internetu.');
    }
    // Pokud je to chyba sítě (server spadl, chybí internet)
    if (err instanceof TypeError && err.message === 'Failed to fetch') {
      throw new Error('Nelze se spojit se serverem. Zkontrolujte připojení k internetu.');
    }
    
    // Jiná chyba (přepošleme ji dál)
    throw err;
  }
}

export default BASE_URL;