import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-carte-etape',
  standalone: true,
  imports: [MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="bg-surface-container-lowest dark:bg-inverse-surface rounded-2xl p-8 border border-surface-variant dark:border-outline/40 shadow-sm hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
      <div class="absolute top-0 right-0 w-24 h-24 bg-primary/5 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-125"></div>
      
      <div class="w-16 h-16 rounded-2xl bg-primary/10 dark:bg-primary/20 flex items-center justify-center mb-6 text-primary dark:text-inverse-primary relative z-10">
        <mat-icon class="text-3xl">{{ iconeMat }}</mat-icon>
      </div>

      <h3 class="text-xl font-bold text-on-background dark:text-inverse-on-surface mb-3 relative z-10">
        {{ titreEtape }}
      </h3>

      <p class="text-on-surface-variant dark:text-outline-variant text-sm leading-relaxed relative z-10">
        {{ descriptionEtape }}
      </p>
    </div>
  `
})
export class ComposantCarteEtape {
  @Input({ required: true }) public iconeMat!: string;
  @Input({ required: true }) public titreEtape!: string;
  @Input({ required: true }) public descriptionEtape!: string;
}
