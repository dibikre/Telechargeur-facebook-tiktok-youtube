import {
  AngularNodeAppEngine,
  createNodeRequestHandler,
  isMainModule,
  writeResponseToNodeResponse,
} from '@angular/ssr/node';
import express from 'express';
import {join} from 'node:path';
import { GestionnaireExtractionMedia } from './serveur/traitements/gestionnaire_extraction';

const browserDistFolder = join(import.meta.dirname, '../browser');

const app = express();
app.use(express.json());

const angularApp = new AngularNodeAppEngine({
  allowedHosts: ['*'],
});

const gestionnaireExtraction = new GestionnaireExtractionMedia();

/**
 * Endpoints API Backend pour l'extraction de médias
 */
app.post('/api/extraire', async (req, res) => {
  const urlSaisie = req.body?.url || req.query?.url;
  if (!urlSaisie || typeof urlSaisie !== 'string') {
    res.status(400).json({ success: false, error: 'Veuillez fournir une adresse URL valide.' });
    return;
  }

  try {
    const resultat = await gestionnaireExtraction.traiterExtraction(urlSaisie);
    if (!resultat) {
      res.status(404).json({ success: false, error: "Impossible d'extraire la vidéo. Vérifiez que l'URL est publique." });
      return;
    }
    res.json({ success: true, data: resultat });
  } catch (erreur) {
    res.status(500).json({ success: false, error: "Une erreur interne s'est produite lors de l'extraction." });
  }
});

app.get('/api/extraire', async (req, res) => {
  const urlSaisie = req.query?.url;
  if (!urlSaisie || typeof urlSaisie !== 'string') {
    res.status(400).json({ success: false, error: 'Veuillez fournir une adresse URL valide.' });
    return;
  }

  try {
    const resultat = await gestionnaireExtraction.traiterExtraction(urlSaisie);
    if (!resultat) {
      res.status(404).json({ success: false, error: "Impossible d'extraire la vidéo. Vérifiez que l'URL est publique." });
      return;
    }
    res.json({ success: true, data: resultat });
  } catch (erreur) {
    res.status(500).json({ success: false, error: "Une erreur interne s'est produite lors de l'extraction." });
  }
});

app.get('/api/proxy', async (req, res) => {
  const urlCible = req.query?.url;
  const nomFichier = req.query?.nom_fichier || 'media_telecharge.mp4';

  if (!urlCible || typeof urlCible !== 'string') {
    res.status(400).send('URL cible manquante.');
    return;
  }

  try {
    const reponseComplet = await fetch(urlCible, {
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'
      }
    });

    if (!reponseComplet.ok || !reponseComplet.body) {
      res.status(reponseComplet.status).send('Erreur lors de la récupération du flux distant.');
      return;
    }

    res.setHeader('Content-Type', reponseComplet.headers.get('content-type') || 'application/octet-stream');
    res.setHeader('Content-Disposition', `attachment; filename="${encodeURIComponent(String(nomFichier))}"`);
    if (reponseComplet.headers.get('content-length')) {
      res.setHeader('Content-Length', reponseComplet.headers.get('content-length')!);
    }

    const { Readable } = await import('node:stream');
    // @ts-expect-error stream conversion
    Readable.fromWeb(reponseComplet.body).pipe(res);
  } catch (erreur) {
    res.status(500).send('Erreur proxy de téléchargement.');
  }
});

/**
 * Serve static files from /browser
 */
app.use(
  express.static(browserDistFolder, {
    maxAge: '1y',
    index: false,
    redirect: false,
  }),
);

/**
 * Handle all other requests by rendering the Angular application.
 */
app.use((req, res, next) => {
  angularApp
    .handle(req)
    .then((response) =>
      response ? writeResponseToNodeResponse(response, res) : next(),
    )
    .catch(next);
});

/**
 * Start the server if this module is the main entry point, or it is ran via PM2.
 * The server listens on the port defined by the `PORT` environment variable, or defaults to 4000.
 */
if (isMainModule(import.meta.url) || process.env['pm_id']) {
  const port = process.env['PORT'] || 4000;
  app.listen(port, (error) => {
    if (error) {
      throw error;
    }

    console.log(`Node Express server listening on http://localhost:${port}`);
  });
}

/**
 * Request handler used by the Angular CLI (for dev-server and during build) or Firebase Cloud Functions.
 */
export const reqHandler = createNodeRequestHandler(app);
