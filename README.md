<p align="center"><img src="logo.svg" width="96" alt="dotmcombinationonlisting"></p>

# dotmcombinationonlisting — Combinazioni nel listato per PrestaShop

Modulo PrestaShop che mostra le **combinazioni dei prodotti come elementi separati nei listati delle categorie**: ad esempio una maglietta disponibile in tre colori compare tre volte, una per colore, ciascuna con la sua immagine, il suo prezzo e il link alla combinazione giusta. Da back office scegli **per ogni prodotto quali valori mostrare**.

> *English summary: PrestaShop module that shows product combinations as separate items in category listings (e.g. one tile per colour, with the combination's image, price and link). You choose the attribute group to split, the categories, and — per product, in the "Modules" tab — which values to show. Works with the core listing and with ps_facetedsearch (filters, sorting and pagination keep working). Back office and texts in Italian and English.*

## Funzionalità

- **Un elemento per valore** del gruppo di attributi scelto (es. «Colore»): nel listato ogni valore diventa un riquadro con
  - l'**immagine** associata alla combinazione (se c'è),
  - il **prezzo** e il **link** della combinazione (la pagina prodotto si apre con quella combinazione già selezionata),
  - il **nome** con il valore aggiunto, facoltativo (es. «T-shirt - Blu»).
- **Scelta dei valori per prodotto**, nella scheda *Moduli* della pagina prodotto: caselle con i valori del gruppo presenti nelle combinazioni del prodotto, con «Seleziona tutti» / «Nessuno» e il ripristino dell'impostazione predefinita.
- **Prodotti non configurati**: a scelta restano un elemento unico (predefinito) oppure mostrano tutti i valori.
- **Categorie**: solo quelle selezionate, oppure tutte.
- **Disponibilità**: per ogni valore viene usata la combinazione predefinita se ordinabile, altrimenti la prima ordinabile; i valori senza combinazioni ordinabili si possono nascondere.
- **Filtri, ordinamento e paginazione** continuano a funzionare: il conteggio dei prodotti e le pagine si basano sugli elementi espansi. Con **ps_facetedsearch**, se filtri per un valore dello stesso gruppo (es. Colore = Nero) compaiono solo le combinazioni corrispondenti; i filtri sugli altri gruppi (es. Taglia) restringono le combinazioni scelte.
- **Nessuna dipendenza**: funziona con il listato standard di PrestaShop e con ps_facetedsearch, senza override.

## Requisiti

- PrestaShop **1.7.7** o successivo (provato su PrestaShop 9.1 con ps_facetedsearch 4.0)
- PHP 7.2 o successivo
- Combinazioni attive nel negozio (*Parametri negozio → Impostazioni prodotti*)

## Installazione

1. Scarica la repo (*Code → Download ZIP*, oppure una release).
2. La cartella del modulo **deve chiamarsi `dotmcombinationonlisting`**. Lo ZIP di GitHub contiene la cartella `ps_combinationonlisting-main`: rinominala e ricrea lo ZIP:
   ```sh
   unzip ps_combinationonlisting-main.zip && mv ps_combinationonlisting-main dotmcombinationonlisting
   zip -r dotmcombinationonlisting.zip dotmcombinationonlisting -x '*.git*' -x '*/CLAUDE.md'
   ```
3. In back office: *Moduli → Gestione moduli → Carica un modulo* e scegli `dotmcombinationonlisting.zip`.
   In alternativa copia la cartella in `modules/dotmcombinationonlisting/` sul server e installa il modulo dal Gestore moduli.
4. Apri *Configura*, scegli il gruppo di attributi e le categorie (vedi sotto).

L'installazione crea la tabella `ps_dotmcombinationonlisting_value` e aggancia il modulo a `productSearchProvider` (in **prima posizione**), `actionPresentProductListing`, `actionPresentProduct`, `displayAdminProductsExtra`, `actionProductUpdate`, `actionAfterUpdateProductFormHandler`, `actionProductDelete`.

## Configurazione

| Impostazione | Predefinito | Note |
|---|---|---|
| Mostra le combinazioni nei listati | Sì | Interruttore generale. |
| Gruppo di attributi da separare | il primo gruppo di tipo colore | Un elemento per ogni valore di questo gruppo. Gli altri attributi (es. taglia) si scelgono nella pagina prodotto. |
| Applica a tutte le categorie | No | Se attivo, l'elenco delle categorie viene ignorato. |
| Categorie | nessuna | Le sottocategorie non sono incluse automaticamente: vanno selezionate una per una. |
| Prodotti non configurati: mostra tutti i valori | No | Se attivo, i prodotti in cui non hai scelto i valori mostrano tutti i valori del gruppo. |
| Nascondi i valori non disponibili | Sì | Nasconde un valore se nessuna delle sue combinazioni è ordinabile (con la gestione delle scorte attiva). |
| Aggiungi il valore al nome del prodotto | Sì | Es. «T-shirt - Blu». |

### Scelta dei valori nel prodotto

In *Catalogo → Prodotti → (prodotto) → Moduli → Combinazioni nel listato* trovi i valori del gruppo presenti nelle combinazioni del prodotto, con il numero di combinazioni per valore:

- **alcuni valori selezionati**: nel listato compare un elemento per ciascuno;
- **nessun valore selezionato**: il prodotto compare come elemento unico, come senza modulo;
- **mai configurato**: vale l'impostazione «Prodotti non configurati» del modulo. La casella «Ripristina l'impostazione predefinita» cancella la scelta e riporta il prodotto a questo stato.

Le scelte si salvano con il pulsante *Salva* del prodotto. In multinegozio sono separate per negozio.

## Come funziona

Sulle pagine categoria PrestaShop chiede ai moduli agganciati a `productSearchProvider` chi gestisce la ricerca, e usa il primo che risponde. Il modulo si mette in prima posizione e, per le categorie abilitate:

1. individua il provider che sarebbe stato usato senza di lui (ps_facetedsearch o, in mancanza, quello del core) e lo **avvolge**;
2. gli chiede **tutti** i prodotti della categoria, già filtrati e ordinati;
3. **espande** ogni prodotto in un elemento per valore scelto, mantenendo l'ordine, e sceglie per ciascuno la combinazione da mostrare;
4. pagina sugli elementi espansi e restituisce la pagina richiesta, con i filtri del provider originale;
5. alla presentazione dei prodotti (`actionPresentProductListing`) applica nome e immagine della combinazione.

Nelle altre categorie, nella ricerca, nelle pagine marca/fornitore, novità e promozioni il listato resta quello normale.

## Dopo l'installazione

- Controlla che **ps_facetedsearch** (o l'altro modulo di filtri) sia agganciato a `productSearchProvider` **dopo** questo modulo (*Aspetto → Posizioni*): l'installazione e ogni salvataggio della configurazione rimettono il modulo in prima posizione.
- Associa le **immagini alle combinazioni** (pagina prodotto → *Combinazioni*), altrimenti tutti gli elementi mostrano la copertina del prodotto.
- Svuota la cache in *Parametri avanzati → Prestazioni* se il listato non cambia.

## Personalizzazione

Il modulo non contiene nulla di specifico per un negozio o un tema: tutto passa dalla configurazione.

- **Template della scheda prodotto**: `views/templates/admin/product_tab.tpl`.
- **Nel tema** puoi riconoscere gli elementi espansi dal valore `product.dcol_id_product_attribute` (id della combinazione mostrata) nel template `catalog/_partials/miniatures/product.tpl`.
- **Testi**: sono in `lang/it.php` (riferimento) e `lang/en.php`. Per un'altra lingua aggiungi `lang/<iso>.php` con le stesse chiavi (basta tradurre quelle che vuoi: le mancanti arrivano dall'inglese). Le lingue senza file usano l'inglese.

### Limiti

- Per paginare sugli elementi espansi il modulo legge l'intero elenco della categoria a ogni richiesta: su categorie con molte migliaia di prodotti valuta i tempi di risposta (con ps_facetedsearch la lettura è leggera, col provider del core un po' meno).
- Con l'ordinamento casuale i prodotti non vengono espansi.
- Altri moduli di filtri che non usano `productSearchProvider` (es. che sostituiscono il listato via ajax con un proprio meccanismo) non sono supportati.

## Struttura

```
dotmcombinationonlisting.php                              classe del modulo: installazione, configurazione, espansione, hook
classes/DotmCombinationOnListingSearchProvider.php        provider di ricerca che avvolge quello originale
views/templates/admin/product_tab.tpl                     scheda «Moduli» del prodotto
lang/                                                     testi (it, en)
```

## Aggiornamento e disinstallazione

- Per aggiornare, sostituisci i file del modulo e avvia l'aggiornamento dal Gestore moduli: gli eventuali script in `upgrade/` aggiornano database e configurazione.
- Disinstallare il modulo cancella la tabella `ps_dotmcombinationonlisting_value` con le scelte fatte nei prodotti e la configurazione; il listato torna quello normale.

## Licenza

Rilasciato con licenza [MIT](LICENSE).

## Autore

Sviluppato da [dotm.it](https://dotm.it).
