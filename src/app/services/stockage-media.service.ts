import { Injectable, signal } from '@angular/core';
import { MetadonneesMedia } from '../modeles/media.modele';
import { OptionFormatMedia } from '../modeles/format.modele';

export interface ElementHistoriqueMedia {
  identifiant: string;
  media: MetadonneesMedia;
  formatUtilise: OptionFormatMedia;
  dateTelechargement: string;
  tailleFichierTexte: string;
}

@Injectable({
  providedIn: 'root'
})
export class ServiceStockageMedia {
  public historique = signal<ElementHistoriqueMedia[]>([]);

  private CLE_STOCKAGE_HISTORIQUE = 'mediafetch_historique_fichiers';

  constructor() {
    this.chargerHistoriqueDepuisStockage();
  }

  private chargerHistoriqueDepuisStockage(): void {
    if (typeof window === 'undefined') return;
    try {
      const donneeSauvegardee = localStorage.getItem(this.CLE_STOCKAGE_HISTORIQUE);
      if (donneeSauvegardee) {
        this.historique.set(JSON.parse(donneeSauvegardee));
      } else {
        this.initialiserExemplesInitiales();
      }
    } catch {
      this.initialiserExemplesInitiales();
    }
  }

  private initialiserExemplesInitiales(): void {
    const exemples: ElementHistoriqueMedia[] = [
      {
        identifiant: 'hist-1',
        media: {
          identifiant: 'demo-1',
          titre: 'Understanding Advanced Data Structures in Modern Applications',
          auteur: 'TechCorp Media',
          dureeTexte: '14:23',
          dureeSecondes: 863,
          miniatureUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDFDlhdSHbaSLwmyMF0-6FxZYdLfHWkpyvsUGGH6A1CRPgJQm-4Z7BA0l9sDTMjqf6IP94yq7zGnJ0A53lbJYDcRrvW1a5ogZ-5bvVoRnq4NiJzVn_GoJoC58dkdp7ifhqGlhZGyQm7KpLLAIrV6Xl1BTsyfjPxmtg0dfvBoCdu-wkI6XxS244D4C5nPRLv-70ID84uSIe0zESGaFxguvF9IGp6UqsDwv2uIvIDE9_pV_gtR4u874R_u5dAnvEGaVakT5719zv7B_s',
          plateformeNom: 'YouTube',
          plateformeId: 'youtube',
          adresseOriginale: 'https://youtube.com/watch?v=demo1',
          dateAjout: new Date()
        },
        formatUtilise: {
          identifiant: 'mp4-1080p',
          nomFormat: 'MP4 1080p (HD)',
          qualiteLabel: 'Full HD 1080p',
          tailleEstimeeOctets: 152043520,
          tailleTexte: '~145 MB',
          typeContenu: 'video',
          extension: 'mp4',
          estHauteDefinition: true,
          iconeNom: 'high_quality'
        },
        dateTelechargement: new Date().toLocaleDateString('fr-FR'),
        tailleFichierTexte: '~145 MB'
      }
    ];
    this.historique.set(exemples);
    this.sauvegarderLocalement();
  }

  public ajouterHistorique(media: MetadonneesMedia, format: OptionFormatMedia): void {
    const nouvelElement: ElementHistoriqueMedia = {
      identifiant: Math.random().toString(36).substring(2, 9),
      media,
      formatUtilise: format,
      dateTelechargement: new Date().toLocaleDateString('fr-FR'),
      tailleFichierTexte: format.tailleTexte
    };

    this.historique.update(liste => [nouvelElement, ...liste]);
    this.sauvegarderLocalement();
  }

  public viderHistorique(): void {
    this.historique.set([]);
    this.sauvegarderLocalement();
  }

  public supprimerElement(id: string): void {
    this.historique.update(liste => liste.filter(e => e.identifiant !== id));
    this.sauvegarderLocalement();
  }

  private sauvegarderLocalement(): void {
    if (typeof window === 'undefined') return;
    try {
      localStorage.setItem(this.CLE_STOCKAGE_HISTORIQUE, JSON.stringify(this.historique()));
    } catch {
      // Ignorer erreur de quota
    }
  }
}
