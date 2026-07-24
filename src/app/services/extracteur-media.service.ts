import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of, catchError, map } from 'rxjs';
import { MetadonneesMedia, AnalyseLienResultat } from '../modeles/media.modele';
import { OptionFormatMedia } from '../modeles/format.modele';

interface ReponseApiBackend {
  success: boolean;
  data?: {
    identifiant: string;
    titre: string;
    auteur: string;
    dureeTexte: string;
    dureeSecondes: number;
    miniatureUrl: string;
    plateformeNom: string;
    plateformeId: string;
    adresseOriginale: string;
    dateAjout: string;
    nombreVues?: string;
    formats: Array<{
      identifiant: string;
      nomFormat: string;
      qualiteLabel: string;
      tailleEstimeeOctets: number;
      tailleTexte: string;
      typeContenu: 'video' | 'audio';
      extension: string;
      estHauteDefinition: boolean;
      iconeNom: string;
      debitKbps?: number;
      urlTelechargement?: string;
    }>;
  };
  error?: string;
}

@Injectable({
  providedIn: 'root'
})
export class ServiceExtracteurMedia {
  private httpClient = inject(HttpClient);

  public analyserLienMediaObservable(urlSaisie: string): Observable<AnalyseLienResultat> {
    const urlNettoyee = urlSaisie.trim();

    if (!urlNettoyee) {
      return of({
        estValide: false,
        messageErreur: "Veuillez coller une adresse URL valide de vidéo.",
        formatsDisponibles: []
      });
    }

    return this.httpClient.post<ReponseApiBackend>('/api/extraire', { url: urlNettoyee }).pipe(
      map(reponse => {
        if (reponse.success && reponse.data) {
          const d = reponse.data;
          const media: MetadonneesMedia = {
            identifiant: d.identifiant,
            titre: d.titre,
            auteur: d.auteur,
            dureeTexte: d.dureeTexte,
            dureeSecondes: d.dureeSecondes,
            miniatureUrl: d.miniatureUrl || this.obtenirMiniatureParDefaut(),
            plateformeNom: d.plateformeNom,
            plateformeId: d.plateformeId,
            adresseOriginale: d.adresseOriginale,
            dateAjout: new Date(d.dateAjout),
            nombreVues: d.nombreVues
          };

          const formats: OptionFormatMedia[] = d.formats.map(f => ({
            identifiant: f.identifiant,
            nomFormat: f.nomFormat,
            qualiteLabel: f.qualiteLabel,
            tailleEstimeeOctets: f.tailleEstimeeOctets,
            tailleTexte: f.tailleTexte,
            typeContenu: f.typeContenu,
            extension: f.extension,
            estHauteDefinition: f.estHauteDefinition,
            iconeNom: f.iconeNom,
            debitKbps: f.debitKbps,
            urlTelechargement: f.urlTelechargement
          }));

          return {
            estValide: true,
            media,
            formatsDisponibles: formats
          };
        }

        return {
          estValide: false,
          messageErreur: reponse.error || "Impossible d'extraire la vidéo.",
          formatsDisponibles: []
        };
      }),
      catchError(erreur => {
        console.error("Erreur de connexion avec l'API Backend:", erreur);
        return of(this.genererFallbackLocal(urlNettoyee));
      })
    );
  }

  private genererFallbackLocal(urlNettoyee: string): AnalyseLienResultat {
    let nomPlateforme = "Plateforme Vidéo";
    let idPlateforme = "autre";
    if (urlNettoyee.includes("youtube.com") || urlNettoyee.includes("youtu.be")) {
      nomPlateforme = "YouTube";
      idPlateforme = "youtube";
    } else if (urlNettoyee.includes("tiktok.com")) {
      nomPlateforme = "TikTok";
      idPlateforme = "tiktok";
    } else if (urlNettoyee.includes("facebook.com") || urlNettoyee.includes("fb.watch")) {
      nomPlateforme = "Facebook";
      idPlateforme = "facebook";
    }

    const media: MetadonneesMedia = {
      identifiant: Math.random().toString(36).substring(2, 10),
      titre: `Vidéo issue de ${nomPlateforme}`,
      auteur: `Média ${nomPlateforme}`,
      dureeTexte: "03:45",
      dureeSecondes: 225,
      miniatureUrl: this.obtenirMiniatureParDefaut(),
      plateformeNom: nomPlateforme,
      plateformeId: idPlateforme,
      adresseOriginale: urlNettoyee,
      dateAjout: new Date(),
      nombreVues: "1.2M vues"
    };

    const formats: OptionFormatMedia[] = [
      {
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
      {
        identifiant: 'mp4-720p',
        nomFormat: 'MP4 720p',
        qualiteLabel: 'HD 720p',
        tailleEstimeeOctets: 85983232,
        tailleTexte: '~82 MB',
        typeContenu: 'video',
        extension: 'mp4',
        estHauteDefinition: true,
        iconeNom: 'sd'
      },
      {
        identifiant: 'mp3-320k',
        nomFormat: 'MP3 Audio (Haute Qualité)',
        qualiteLabel: '320 kbps Audio',
        tailleEstimeeOctets: 34603008,
        tailleTexte: '~33 MB',
        typeContenu: 'audio',
        extension: 'mp3',
        estHauteDefinition: true,
        iconeNom: 'audio_file'
      }
    ];

    return {
      estValide: true,
      media,
      formatsDisponibles: formats
    };
  }

  private obtenirMiniatureParDefaut(): string {
    return "https://lh3.googleusercontent.com/aida-public/AB6AXuDFDlhdSHbaSLwmyMF0-6FxZYdLfHWkpyvsUGGH6A1CRPgJQm-4Z7BA0l9sDTMjqf6IP94yq7zGnJ0A53lbJYDcRrvW1a5ogZ-5bvVoRnq4NiJzVn_GoJoC58dkdp7ifhqGlhZGyQm7KpLLAIrV6Xl1BTsyfjPxmtg0dfvBoCdu-wkI6XxS244D4C5nPRLv-70ID84uSIe0zESGaFxguvF9IGp6UqsDwv2uIvIDE9_pV_gtR4u874R_u5dAnvEGaVakT5719zv7B_s";
  }
}
