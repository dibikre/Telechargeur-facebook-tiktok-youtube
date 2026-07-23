import { OptionFormatMedia } from './format.modele';

export interface MetadonneesMedia {
  identifiant: string;
  titre: string;
  auteur: string;
  dureeTexte: string;
  dureeSecondes: number;
  miniatureUrl: string;
  plateformeNom: string;
  plateformeId: string;
  adresseOriginale: string;
  dateAjout: Date;
  nombreVues?: string;
}

export interface AnalyseLienResultat {
  estValide: boolean;
  messageErreur?: string;
  media?: MetadonneesMedia;
  formatsDisponibles: OptionFormatMedia[];
}
