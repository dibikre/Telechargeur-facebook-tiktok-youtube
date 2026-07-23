import { Injectable, signal } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class ServiceTheme {
  public estModeSombre = signal<boolean>(false);

  constructor() {
    this.initialiserTheme();
  }

  private initialiserTheme(): void {
    if (typeof window !== 'undefined') {
      const themeEnregistre = localStorage.getItem('mediafetch_theme');
      if (themeEnregistre) {
        this.estModeSombre.set(themeEnregistre === 'sombre');
      } else {
        const preferenceSysteme = window.matchMedia('(prefers-color-scheme: dark)').matches;
        this.estModeSombre.set(preferenceSysteme);
      }
      this.appliquerTheme();
    }
  }

  public basculerTheme(): void {
    this.estModeSombre.update(valeur => !valeur);
    if (typeof window !== 'undefined') {
      localStorage.setItem('mediafetch_theme', this.estModeSombre() ? 'sombre' : 'clair');
    }
    this.appliquerTheme();
  }

  private appliquerTheme(): void {
    if (typeof document !== 'undefined') {
      const elementRacine = document.documentElement;
      if (this.estModeSombre()) {
        elementRacine.classList.add('dark');
        elementRacine.classList.remove('light');
      } else {
        elementRacine.classList.remove('dark');
        elementRacine.classList.add('light');
      }
    }
  }
}
