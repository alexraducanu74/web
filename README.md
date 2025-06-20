# BoW (Books on Web) - Documentatie Proiect
Autori: Raducanu Alexandru, Sacaleanu Stefan-Bogdan

## Prezentare Arhitectura - Aplicatie Gestiune Carti si Grupuri de Lectura
Acest document ofera o prezentare de forma C4 asupra arhitecturii aplicatiei web "Books on Web", o platforma pentru gestionarea cartilor, a progresului de lectura si a grupurilor de socializare. 

##  Nivel 1 - Context
Aplicatia este o platforma web prin care utilizatorii pot:

Vizualiza, cauta si filtra carti dintr-o baza de date locala,
inregistra un cont nou iar apoi pot folosi functia de autentificaare,
salva progresul lecturii (pagini citite), adauga recenzii si acorda rating-uri cartilor,
vizualiza o pagina personala ("My Books") cu toate cartile la care au inregistrat progres,
crea grupuri de lectura, fiecare cu un cod secret de invitatie,
se alatura altor grupuri folosind codul secret,
vizualiza progresul de lectura al membrilor unui grup pentru o anumita carte,
accesa un flux RSS cu ultimele carti si recenzii adaugate,
gasi biblioteci publice in proximitate (via API extern) daca o cautare nu returneaza rezultate.
### Actori principali:

#### Utilizator autentificat: Are acces la toate functionalitatile de baza (recenzii, progres, grupuri).
#### Administrator: Pe langa functionalitatile de utilizator, poate adauga, edita si sterge carti din sistem.
#### Serviciul extern Nominatim (OpenStreetMap): Utilizat pentru a oferi sugestii de biblioteci din apropiere.
## Nivel 2 - Container
Arhitectura este construita pe modelul MVC (Model-View-Controller) si include urmatoarele containere logice:

#### Container / Rol principal	Limbaj / Tehnologie
Frontend -> Interfata utilizator -> HTML, CSS, JavaScript  
Backend	-> Logica aplicatiei si gestionarea datelor	-> PHP 8+  
Baza de date -> Persistenta datelor (carti, utilizatori, grupuri etc.)	-> PostgreSQL  
API extern ->	Serviciul de geolocatie Nominatim	-> REST API JSON  

## Nivel 3 - Componente
Aplicatia este organizata in componente conform modelului MVC, cu o separare clara a responsabilitatilor:

### Controllere (controllers/)

Controller.php -> Controller abstract de baza ce ofera functionalitati comune, precum verificarea utilizatorului autentificat prin JWT.  
ControllerAuth.php -> Gestioneaza logica de autentificare, inregistrare si delogare.  
ControllerFeed.php -> Administreaza afisarea listei de carti, cautarea, paginile de detalii, sectiunea "My Books" si salvarea recenziilor.  
ControllerGroup.php -> Contine logica pentru crearea, vizualizarea si administrarea grupurilor de lectura, inclusiv aderarea membrilor si gestionarea cererilor.  
ControllerApiFeed.php -> Expune endpoint-uri API pentru operatiuni administrative (adaugare/stergere/modificare carti) si pentru generarea fluxului RSS.  
ControllerStats.php -> Se ocupa de exportul de statistici agregate in formatele CSV si DocBook (XML).  
### Modele (models/)

Contin logica de acces si manipulare a datelor. Toate interactiunile cu baza de date se fac prin intermediul modelelor, folosind PDO si prepared statements.  
ModelFeed.php -> Gestioneaza operatiunile CRUD pentru carti, citirea progresului utilizatorilor si interactiunea cu API-ul Nominatim.  
ModelSignup.php & ModelLogin.php -> Se ocupa de inregistrarea, respectiv validarea utilizatorilor.  
ModelGroup.php -> Implementeaza logica pentru grupuri, membri, si cartile asociate acestora.  
ModelStats.php -> Calculeaza si extrage date statistice din baza de date.  
### View-uri (views/)

Sunt clase PHP responsabile cu randarea output-ului HTML, adesea incarcand fisiere de tip .tpl.
ViewFeed.php -> Afiseaza lista de carti, pagina de detalii a unei carti si formularele de administrare.  
ViewLogin.php & ViewRegister.php -> Randareaza formularele de autentificare si inregistrare.  
ViewGroup.php -> Afiseaza paginile specifice grupurilor (creare, vizualizare, administrare).  
### API intern

Aplicatia utilizeaza un router simplu (index.php) care directioneaza cererile API catre ControllerApiFeed.  
/index.php?api=1&actiune=insertBookApi: Adauga o carte noua (admin).  
/index.php?api=1&actiune=deleteBookApi&parametri={id}: Sterge o carte (admin).  
/index.php?api=1&actiune=genereazaRssApi: Genereaza un feed RSS cu noutati.  
De asemenea, exista un endpoint AJAX (?controller=feed&actiune=ajaxFilterBooks) pentru actualizarea dinamica a listei de carti in functie de filtrele aplicate.  
## Nivel 4 - Cod sursa
Logica este impartita in clase si functii cu responsabilitati clare, respectand principiile separarii preocuparilor.

Rutare -> index.php actioneaza ca un front controller, analizand parametrii GET controller si actiune pentru a instantia clasa corespunzatoare.  
Conexiune BD -> Se utilizeaza un design pattern Singleton in clasa Dbh pentru a asigura o singura instanta a conexiunii PDO in intreaga aplicatie.  
Autoloading -> Aplicatia poate folosi un autoloader custom (autoload.php), cat si autoloader-ul generat de Composer (vendor/autoload.php) pentru a incarca clasele necesare.  
Exemple de functionalitati:
ModelFeed::getBooks() -> Returneaza o lista de carti, aplicand filtre dinamice pentru cautare, autor si gen.  
ControllerAuth::login()-> Gestioneaza procesul de login, iar in caz de succes, genereaza un token JWT.  
ViewFeed::renderBook() -> Primeste datele despre o carte si le afiseaza intr-un format prietenos.  
### Securitate
Autentificare -> Sistemul foloseste JSON Web Tokens (JWT) stocate in sesiunea PHP. Token-ul contine ID-ul, username-ul si rolul utilizatorului (admin/user).  
Autorizare -> Actiunile critice, precum modificarea cartilor, sunt protejate verificand campul is_admin din payload-ul JWT. Drepturile de administrare a unui grup sunt verificate prin compararea ID-ului utilizatorului curent cu cel al creatorului grupului.  
Parole -> Parolele sunt criptate la inregistrare folosind password_hash() si verificate cu password_verify().  
Prevenire SQL Injection -> Toate interogarile catre baza de date folosesc prepared statements prin PDO, eliminand riscul de injectii SQL.  
Prevenire XSS -> Datele dinamice sunt escapate in view-uri folosind htmlspecialchars() inainte de a fi afisate in HTML.  
Validare Input -> Se realizeaza validari atat pe partea de server (ex: lungimea numelui de utilizator, formatul email-ului), cat si la nivel de baza de date prin triggere (ex: lungimea minima pentru numele grupurilor).  
### Tehnologii utilizate
Limbaj Server-side -> PHP 8+  
Baza de date -> PostgreSQL  
Limbaje Client-side -> HTML5, CSS3, JavaScript  
Management Dependinte -> Composer  
Biblioteci PHP -> firebase/php-jwt pentru gestionarea token-urilor de autentificare.  
API-uri Externe -> Nominatim API (REST) pentru geolocalizare.  
Formate de Date -> RSS, CSV, DocBook.  
