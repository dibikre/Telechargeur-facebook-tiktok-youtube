export interface FormatStructure {
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
}

export interface ResultatExtractionBackend {
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
  formats: FormatStructure[];
}

export interface InterfaceRecuperateurBackend {
  correspondA(urlVideo: string): boolean;
  extraire(urlVideo: string): Promise<ResultatExtractionBackend | null>;
}
