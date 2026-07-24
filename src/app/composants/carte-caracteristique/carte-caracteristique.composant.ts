import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-carte-caracteristique',
  standalone: true,
  imports: [MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="bg-white dark:bg-surface-container-high rounded-2xl p-6 sm:p-8 border border-outline-variant/60 dark:border-outline shadow-sm text-center flex flex-col items-center hover:-translate-y-1 transition-all duration-300">
      <div class="w-16 h-16 rounded-2xl bg-primary/10 dark:bg-primary/20 flex items-center justify-center mb-6 text-primary dark:text-inverse-primary">
        <mat-icon class="text-3xl">{{ iconeMat }}</mat-icon>
      </div>

      <h3 class="text-xl font-bold text-black dark:text-black mb-3">
        {{ titreCaracteristique }}
      </h3>

      <p class="text-black dark:text-black text-sm leading-relaxed">
        {{ descriptionCaracteristique }}
      </p>
    </div>
  `
})
export class ComposantCarteCaracteristique {
  @Input({ required: true }) public iconeMat!: string;
  @Input({ required: true }) public titreCaracteristique!: string;
  @Input({ required: true }) public descriptionCaracteristique!: string;
}
