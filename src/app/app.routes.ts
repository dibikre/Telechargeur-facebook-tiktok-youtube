import { Routes } from '@angular/router';
import { ComposantPageAccueil } from './pages/page-accueil/page-accueil.composant';
import { ComposantPageResultats } from './pages/page-resultats/page-resultats.composant';
import { ComposantPageCommentUtiliser } from './pages/page-comment-utiliser/page-comment-utiliser.composant';
import { ComposantPageSitesSupportes } from './pages/page-sites-supportes/page-sites-supportes.composant';
import { ComposantPageFaq } from './pages/page-faq/page-faq.composant';
import { ComposantPageHistorique } from './pages/page-historique/page-historique.composant';

export const routes: Routes = [
  { path: '', component: ComposantPageAccueil },
  { path: 'resultats', component: ComposantPageResultats },
  { path: 'comment-utiliser', component: ComposantPageCommentUtiliser },
  { path: 'sites-supportes', component: ComposantPageSitesSupportes },
  { path: 'faq', component: ComposantPageFaq },
  { path: 'historique', component: ComposantPageHistorique },
  { path: '**', redirectTo: '' }
];
