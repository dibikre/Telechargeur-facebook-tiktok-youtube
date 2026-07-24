import { InterfaceRecuperateurBackend, ResultatExtractionBackend, FormatStructure } from '../interfaces/interface_recuperateur';
import { ExecuteurYtdlp } from '../utilitaires/executeur_ytdlp';

export class RecuperateurFacebookBackend implements InterfaceRecuperateurBackend {
  public correspondA(urlVideo: string): boolean {
    return /facebook\.com|fb\.watch|fb\.gg/i.test(urlVideo);
  }

  public async extraire(urlVideo: string): Promise<ResultatExtractionBackend | null> {
    const donneesJson = await ExecuteurYtdlp.extraireDonneesJson(urlVideo);
    if (!donneesJson) {
      return null;
    }

    const identifiant = String(donneesJson['id'] || 'fb_' + Date.now());
    const titre = String(donneesJson['title'] || 'Vidéo Facebook');
    const auteur = String(donneesJson['uploader'] || 'Page Facebook');
    const dureeSecondes = Number(donneesJson['duration'] || 0);
    const dureeTexte = `${Math.floor(dureeSecondes / 60)}:${Math.floor(dureeSecondes % 60).toString().padStart(2, '0')}`;
    const miniatureUrl = String(donneesJson['thumbnail'] || '');

    const formatsBruts = Array.isArray(donneesJson['formats']) ? donneesJson['formats'] : [];
    const formatsConstructes: FormatStructure[] = [];

    for (const f of formatsBruts) {
      const urlTelecharge = f['url'];
      if (!urlTelecharge) continue;

      const hauteur = Number(f['height'] || 0);
      const ext = String(f['ext'] || 'mp4');
      const label = String(f['format_id'] || (hauteur ? `${hauteur}p` : 'MP4'));
      const tailleOctets = Number(f['filesize'] || f['filesize_approx'] || 30000000);
      const tailleMo = Math.round(tailleOctets / (1024 * 1024));

      formatsConstructes.push({
        identifiant: String(f['format_id'] || `fb_${hauteur}`),
        nomFormat: `Facebook (${label.toUpperCase()})`,
        qualiteLabel: label.toUpperCase(),
        tailleEstimeeOctets: tailleOctets,
        tailleTexte: `~${tailleMo} MB`,
        typeContenu: 'video',
        extension: ext,
        estHauteDefinition: label.toLowerCase().includes('hd') || hauteur >= 720,
        iconeNom: 'movie',
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
      plateformeNom: 'Facebook',
      plateformeId: 'facebook',
      adresseOriginale: urlVideo,
      dateAjout: new Date().toISOString(),
      formats: formatsConstructes.slice(0, 5)
    };
  }
}
