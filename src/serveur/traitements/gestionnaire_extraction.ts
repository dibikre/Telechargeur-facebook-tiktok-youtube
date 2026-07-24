import { InterfaceRecuperateurBackend, ResultatExtractionBackend } from '../interfaces/interface_recuperateur';
import { RecuperateurYouTubeBackend } from './recuperateur_youtube';
import { RecuperateurTikTokBackend } from './recuperateur_tiktok';
import { RecuperateurFacebookBackend } from './recuperateur_facebook';

export class GestionnaireExtractionMedia {
  private recuperateurs: InterfaceRecuperateurBackend[] = [
    new RecuperateurYouTubeBackend(),
    new RecuperateurTikTokBackend(),
    new RecuperateurFacebookBackend(),
  ];

  public async traiterExtraction(urlVideo: string): Promise<ResultatExtractionBackend | null> {
    const urlNettoyee = urlVideo.trim();
    if (!urlNettoyee) {
      return null;
    }

    for (const recuperateur of this.recuperateurs) {
      if (recuperateur.correspondA(urlNettoyee)) {
        return await recuperateur.extraire(urlNettoyee);
      }
    }

    // Essai générique avec YouTube/yt-dlp si aucune règle de domaine explicite
    const recuperateurGenerique = new RecuperateurYouTubeBackend();
    return await recuperateurGenerique.extraire(urlNettoyee);
  }
}
