import { Injectable } from '@angular/core';
import { MetadonneesMedia, AnalyseLienResultat } from '../modeles/media.modele';
import { OptionFormatMedia } from '../modeles/format.modele';

@Injectable({
  providedIn: 'root'
})
export class ServiceExtracteurMedia {
  
  public analyserLienMedia(urlSaisie: string): AnalyseLienResultat {
    const urlNettoyee = urlSaisie.trim();

    if (!urlNettoyee) {
      return {
        estValide: false,
        messageErreur: "Veuillez coller une adresse URL valide de vidéo.",
        formatsDisponibles: []
      };
    }

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
    } else if (urlNettoyee.includes("instagram.com")) {
      nomPlateforme = "Instagram";
      idPlateforme = "instagram";
    } else if (urlNettoyee.includes("vimeo.com")) {
      nomPlateforme = "Vimeo";
      idPlateforme = "vimeo";
    }

    const titreGénéré = this.genererTitreChaine(urlNettoyee, nomPlateforme);
    const auteurGénéré = this.genererNomAuteur(nomPlateforme);
    const miniatureGénérée = this.obtenirMiniatureParDefaut();

    const media: MetadonneesMedia = {
      identifiant: Math.random().toString(36).substring(2, 10),
      titre: titreGénéré,
      auteur: auteurGénéré,
      dureeTexte: "14:23",
      dureeSecondes: 863,
      miniatureUrl: miniatureGénérée,
      plateformeNom: nomPlateforme,
      plateformeId: idPlateforme,
      adresseOriginale: urlNettoyee,
      dateAjout: new Date(),
      nombreVues: "1.4M vues"
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
        iconeNom: 'high_quality',
        debitKbps: 4500
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
        iconeNom: 'sd',
        debitKbps: 2500
      },
      {
        identifiant: 'mp4-480p',
        nomFormat: 'MP4 480p',
        qualiteLabel: 'SD 480p',
        tailleEstimeeOctets: 44040192,
        tailleTexte: '~42 MB',
        typeContenu: 'video',
        extension: 'mp4',
        estHauteDefinition: false,
        iconeNom: 'sd',
        debitKbps: 1200
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
        iconeNom: 'audio_file',
        debitKbps: 320
      },
      {
        identifiant: 'mp3-128k',
        nomFormat: 'MP3 Audio',
        qualiteLabel: '128 kbps Standard',
        tailleEstimeeOctets: 14680064,
        tailleTexte: '~14 MB',
        typeContenu: 'audio',
        extension: 'mp3',
        estHauteDefinition: false,
        iconeNom: 'audio_file',
        debitKbps: 128
      }
    ];

    return {
      estValide: true,
      media,
      formatsDisponibles: formats
    };
  }

  private genererTitreChaine(url: string, plateforme: string): string {
    if (url.toLowerCase().includes("data") || url.toLowerCase().includes("structure")) {
      return "Understanding Advanced Data Structures in Modern Applications";
    }
    if (url.toLowerCase().includes("tuto") || url.toLowerCase().includes("guide")) {
      return "Tutoriel Complet: Maîtriser le développement Web en 2026";
    }
    return `Vidéo originale issue de ${plateforme} - Modèle Haute Définition`;
  }

  private genererNomAuteur(plateforme: string): string {
    const auteurs = ["TechCorp Media", "Studio Créatif", "ApprendreEnLigne", "Canal Officiel", "MédiaFutur"];
    return auteurs[Math.floor(Math.random() * auteurs.length)] + ` (${plateforme})`;
  }

  private obtenirMiniatureParDefaut(): string {
    return "https://lh3.googleusercontent.com/aida-public/AB6AXuDFDlhdSHbaSLwmyMF0-6FxZYdLfHWkpyvsUGGH6A1CRPgJQm-4Z7BA0l9sDTMjqf6IP94yq7zGnJ0A53lbJYDcRrvW1a5ogZ-5bvVoRnq4NiJzVn_GoJoC58dkdp7ifhqGlhZGyQm7KpLLAIrV6Xl1BTsyfjPxmtg0dfvBoCdu-wkI6XxS244D4C5nPRLv-70ID84uSIe0zESGaFxguvF9IGp6UqsDwv2uIvIDE9_pV_gtR4u874R_u5dAnvEGaVakT5719zv7B_s";
  }
}
