import { InterfaceRecuperateurBackend, ResultatExtractionBackend, FormatStructure } from '../interfaces/interface_recuperateur';
import { ExecuteurYtdlp } from '../utilitaires/executeur_ytdlp';

export class RecuperateurTikTokBackend implements InterfaceRecuperateurBackend {
  public correspondA(urlVideo: string): boolean {
    return /tiktok\.com/i.test(urlVideo);
  }

  public async extraire(urlVideo: string): Promise<ResultatExtractionBackend | null> {
    const donneesJson = await ExecuteurYtdlp.extraireDonneesJson(urlVideo);
    if (!donneesJson) {
      return null;
    }

    const identifiant = String(donneesJson['id'] || 'tk_' + Date.now());
    const titre = String(donneesJson['title'] || donneesJson['description'] || 'Vidéo TikTok');
    const auteur = String(donneesJson['uploader'] || donneesJson['creator'] || 'Auteur TikTok');
    const dureeSecondes = Number(donneesJson['duration'] || 0);
    const dureeTexte = `${Math.floor(dureeSecondes / 60)}:${Math.floor(dureeSecondes % 60).toString().padStart(2, '0')}`;
    const miniatureUrl = String(donneesJson['thumbnail'] || '');
    const nombreVues = donneesJson['view_count'] ? `${donneesJson['view_count']} vues` : undefined;

    const formatsBruts = Array.isArray(donneesJson['formats']) ? donneesJson['formats'] : [];
    const formatsConstructes: FormatStructure[] = [];

    for (const f of formatsBruts) {
      const urlTelecharge = f['url'];
      if (!urlTelecharge) continue;

      const hauteur = Number(f['height'] || 0);
      const ext = String(f['ext'] || 'mp4');
      const tailleOctets = Number(f['filesize'] || f['filesize_approx'] || 20000000);
      const tailleMo = Math.round(tailleOctets / (1024 * 1024));

      formatsConstructes.push({
        identifiant: String(f['format_id'] || `tk_${hauteur}`),
        nomFormat: `Vidéo TikTok (${hauteur ? hauteur + 'p' : 'HD'})`,
        qualiteLabel: hauteur ? `${hauteur}p` : 'Originale HD',
        tailleEstimeeOctets: tailleOctets,
        tailleTexte: `~${tailleMo} MB`,
        typeContenu: 'video',
        extension: ext,
        estHauteDefinition: hauteur >= 720,
        iconeNom: 'high_quality',
        urlTelechargement: String(urlTelecharge)
      });
    }

    formatsConstructes.sort((a, b) => b.tailleEstimeeOctets - a.tailleEstimeeOctets);

    return {
      identifiant,
      titre,
      auteur,
      dureeTexte,
      dureeSecondes,
      miniatureUrl,
      plateformeNom: 'TikTok',
      plateformeId: 'tiktok',
      adresseOriginale: urlVideo,
      dateAjout: new Date().toISOString(),
      nombreVues,
      formats: formatsConstructes.slice(0, 5)
    };
  }
}
