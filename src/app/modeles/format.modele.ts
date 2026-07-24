export type TypeMediaContenu = 'video' | 'audio';

export interface OptionFormatMedia {
  identifiant: string;
  nomFormat: string;
  qualiteLabel: string;
  tailleEstimeeOctets: number;
  tailleTexte: string;
  typeContenu: TypeMediaContenu;
  extension: string;
  estHauteDefinition: boolean;
  iconeNom: string;
  debitKbps?: number;
  urlTelechargement?: string;
}
