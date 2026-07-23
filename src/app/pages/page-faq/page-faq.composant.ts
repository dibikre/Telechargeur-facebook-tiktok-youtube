import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { ElementFaq } from '../../modeles/faq.modele';

@Component({
  selector: 'app-page-faq',
  standalone: true,
  imports: [MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <main class="flex-grow flex flex-col items-center py-12 md:py-20 px-4 md:px-10 max-w-4xl mx-auto w-full animation-apparition">
      <div class="text-center mb-12">
        <h1 class="text-3xl md:text-5xl font-black text-on-background dark:text-inverse-on-surface tracking-tight mb-4">
          Foire Aux Questions (FAQ)
        </h1>
        <p class="text-base md:text-lg text-on-surface-variant dark:text-outline-variant">
          Retrouvez les réponses à toutes vos questions sur l'utilisation de MediaFetch.
        </p>
      </div>

      <div class="flex flex-col gap-4 w-full">
        @for (item of questionsFaq(); track item.identifiant) {
          <div class="bg-surface-container-lowest dark:bg-inverse-surface border border-outline-variant/50 dark:border-outline rounded-2xl overflow-hidden transition-all duration-200">
            
            <button (click)="basculerQuestion(item.identifiant)"
                    type="button"
                    class="w-full text-left p-6 flex items-center justify-between gap-4 font-bold text-base text-on-surface dark:text-inverse-on-surface hover:text-primary transition-colors">
              <span>{{ item.question }}</span>
              <mat-icon class="transition-transform duration-300"
                        [class.rotate-180]="item.estDeplie">
                expand_more
              </mat-icon>
            </button>

            @if (item.estDeplie) {
              <div class="px-6 pb-6 text-sm text-on-surface-variant dark:text-outline-variant leading-relaxed border-t border-outline-variant/30 pt-4 animation-apparition">
                {{ item.reponse }}
              </div>
            }
          </div>
        }
      </div>
    </main>
  `
})
export class ComposantPageFaq {
  public questionsFaq = signal<ElementFaq[]>([
    {
      identifiant: 'faq-1',
      question: "MediaFetch est-il totalement gratuit ?",
      reponse: "Oui, MediaFetch est 100% gratuit et sans frais cachés. Vous pouvez télécharger autant de vidéos que vous le souhaitez sans limitation.",
      categorie: 'Général',
      estDeplie: true
    },
    {
      identifiant: 'faq-2',
      question: "Dois-je installer un logiciel sur mon ordinateur ou téléphone ?",
      reponse: "Aucun logiciel ni extension n'est requis. MediaFetch fonctionne directement dans votre navigateur sur PC, Mac, Android et iOS.",
      categorie: 'Utilisation',
      estDeplie: false
    },
    {
      identifiant: 'faq-3',
      question: "Où sont enregistrées les vidéos téléchargées ?",
      reponse: "Les fichiers sont enregistrés directement dans le dossier 'Téléchargements' par défaut de votre navigateur ou de votre appareil.",
      categorie: 'Fichiers',
      estDeplie: false
    },
    {
      identifiant: 'faq-4',
      question: "Puis-je convertir une vidéo YouTube en fichier MP3 Audio ?",
      reponse: "Absolument. Lors de la saisie d'un lien, vous aurez le choix entre plusieurs qualités vidéo (MP4 1080p, 720p) ou l'extraction audio directe en MP3.",
      categorie: 'Formats',
      estDeplie: false
    }
  ]);

  public basculerQuestion(id: string): void {
    this.questionsFaq.update(liste =>
      liste.map(item => item.identifiant === id ? { ...item, estDeplie: !item.estDeplie } : item)
    );
  }
}
