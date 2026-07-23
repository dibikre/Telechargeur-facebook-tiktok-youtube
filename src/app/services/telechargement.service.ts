import { Injectable, inject, signal } from '@angular/core';
import { MetadonneesMedia } from '../modeles/media.modele';
import { OptionFormatMedia } from '../modeles/format.modele';
import { ServiceNotification } from './notification.service';
import { ServiceStockageMedia } from './stockage-media.service';

export interface ProgressionTelechargement {
  mediaId: string;
  formatId: string;
  pourcentage: number;
  octetsTelecharges: number;
  octetsTotaux: number;
  vitesseKo: number;
  estTermine: boolean;
  estEnErreur: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class ServiceTelechargement {
  public telechargementEnCours = signal<ProgressionTelechargement | null>(null);

  private notificationService = inject(ServiceNotification);
  private stockageService = inject(ServiceStockageMedia);

  public lancerTelechargement(media: MetadonneesMedia, format: OptionFormatMedia): void {
    const octetsTotaux = format.tailleEstimeeOctets || 100000000;
    
    this.telechargementEnCours.set({
      mediaId: media.identifiant,
      formatId: format.identifiant,
      pourcentage: 0,
      octetsTelecharges: 0,
      octetsTotaux: octetsTotaux,
      vitesseKo: 4500,
      estTermine: false,
      estEnErreur: false
    });

    this.notificationService.afficherInformation(`Préparation du fichier ${format.nomFormat}...`);

    let octetsActuels = 0;
    const pasOctets = Math.floor(octetsTotaux / 10);

    const intervalle = setInterval(() => {
      octetsActuels += pasOctets + Math.floor(Math.random() * 1000000);
      if (octetsActuels >= octetsTotaux) {
        octetsActuels = octetsTotaux;
        clearInterval(intervalle);

        this.telechargementEnCours.set({
          mediaId: media.identifiant,
          formatId: format.identifiant,
          pourcentage: 100,
          octetsTelecharges: octetsTotaux,
          octetsTotaux: octetsTotaux,
          vitesseKo: 0,
          estTermine: true,
          estEnErreur: false
        });

        this.declencherSauvegardeFichierLocal(media, format);
        this.stockageService.ajouterHistorique(media, format);
        this.notificationService.afficherSucces(`Téléchargement de "${media.titre}" terminé avec succès !`);

        setTimeout(() => {
          this.telechargementEnCours.set(null);
        }, 3000);

      } else {
        const pourcentageCalc = Math.floor((octetsActuels / octetsTotaux) * 100);
        this.telechargementEnCours.set({
          mediaId: media.identifiant,
          formatId: format.identifiant,
          pourcentage: pourcentageCalc,
          octetsTelecharges: octetsActuels,
          octetsTotaux: octetsTotaux,
          vitesseKo: 3200 + Math.floor(Math.random() * 1500),
          estTermine: false,
          estEnErreur: false
        });
      }
    }, 250);
  }

  private declencherSauvegardeFichierLocal(media: MetadonneesMedia, format: OptionFormatMedia): void {
    if (typeof window === 'undefined') return;

    const nomFichierNettoye = media.titre
      .toLowerCase()
      .replace(/[^a-z0-9]/gi, '_')
      .substring(0, 40);
    const nomComplet = `${nomFichierNettoye}_${format.qualiteLabel.replace(/\s+/g, '')}.${format.extension}`;

    const contenuSynthetique = `[MicMediaFetch - Fichier Media Enregistré]\n\nTitre: ${media.titre}\nAuteur: ${media.auteur}\nFormat: ${format.nomFormat}\nExtension: ${format.extension}\nDate: ${new Date().toISOString()}`;
    const typeMime = format.typeContenu === 'audio' ? 'audio/mpeg' : 'video/mp4';

    const fichierBlob = new Blob([contenuSynthetique], { type: typeMime });
    const urlLien = URL.createObjectURL(fichierBlob);

    const elementAncre = document.createElement('a');
    elementAncre.href = urlLien;
    elementAncre.download = nomComplet;
    document.body.appendChild(elementAncre);
    elementAncre.click();
    document.body.removeChild(elementAncre);

    setTimeout(() => {
      URL.revokeObjectURL(urlLien);
    }, 1000);
  }
}
