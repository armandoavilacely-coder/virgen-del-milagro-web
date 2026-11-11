/* Contenido diario de la Virgen del Milagro: oraciones, oraciones a la Virgen, tarde y noche. Lo usan la página y el panel de administración. */
window.VG = (function () {
  "use strict";
  /* Oración del día: 29 temas; la fecha elige una cada día (se repite al terminar el ciclo). */
  var ORACIONES = [
    { t: "Gracias por la vida", f: "Cada día es un regalo de Dios.", p: "Señor, Padre bueno, te doy gracias por este nuevo día, por el aire que respiro y por las personas que me acompañan. Gracias por lo que tengo y también por lo que me falta, porque en todo me enseñas a confiar. Santa María, Virgen del Milagro, enséñame a agradecer con el corazón abierto. Que hoy mis manos sirvan, mis palabras consuelen y mi vida sea una alabanza para ti. Amén." },
    { t: "Paz en el corazón", f: "Que Dios calme lo que te inquieta.", p: "Señor Jesús, tú calmaste la tormenta con una sola palabra. Calma hoy la tormenta de mi corazón. Quita de mí la prisa, el rencor y el miedo, y dame esa paz que nadie puede quitarme. Que en mi casa, en mi trabajo y en mi calle yo sea instrumento de tu paz. Madre del Milagro, cúbreme con tu manto y enséñame a descansar en la voluntad de Dios. Amén." },
    { t: "Protege a mi familia", f: "Bajo tu manto, Madre, está mi hogar.", p: "Dios de amor, te entrego a mi familia: a los que viven conmigo y a los que están lejos. Cuida su salud, bendice su trabajo y guárdalos de todo peligro. Haz que en mi casa reinen el respeto, la paciencia y el perdón. Virgen del Milagro, tú que cuidaste a la Sagrada Familia en Nazaret, cuida también a la mía y mantennos unidos en la fe y en el cariño. Amén." },
    { t: "Fuerza en la prueba", f: "No estás solo en este momento difícil.", p: "Señor, hoy me pesa lo que vivo y siento que mis fuerzas se acaban. Tú conoces mi cansancio y mis lágrimas. Dame valor para dar el siguiente paso, aunque sea pequeño, y no permitas que pierda la esperanza. Recuérdame que ninguna prueba dura para siempre y que tú caminas a mi lado. Madre del Milagro, que estuviste firme junto a la cruz, sostenme cuando yo no pueda más. Amén." },
    { t: "Esperanza que no se apaga", f: "Dios sigue obrando aunque no lo veas.", p: "Dios fiel, a veces el camino se vuelve oscuro y me cuesta ver la salida. Enciende en mí la luz de la esperanza. Ayúdame a creer que tus promesas se cumplen a su tiempo y que nada de lo que sufro es en vano. Que no me rinda, que no me amargue y que sepa esperar con confianza. Virgen del Milagro, estrella de los que caminan, guíame hasta el amanecer. Amén." },
    { t: "Perdón y reconciliación", f: "Perdonar también te sana a ti.", p: "Señor, tú me has perdonado tanto y a mí me cuesta perdonar. Toca mi corazón herido y ablanda lo que se endureció. Dame la gracia de soltar el rencor, de pedir perdón a quien ofendí y de perdonar a quien me hizo daño, aunque no me lo pida. No quiero cargar más con esta herida. Madre del Milagro, enséñame a mirar a los demás con tus ojos de ternura. Amén." },
    { t: "Bendice mi trabajo", f: "Que no falte el pan en tu mesa.", p: "Padre providente, bendice el trabajo de mis manos. Ayúdame a hacerlo con honradez y alegría, y no permitas que falte el pan en mi mesa ni en la de mis hermanos. Por quienes buscan empleo, por quienes trabajan en el campo y en las calles, por quienes tienen deudas y preocupaciones: abre puertas, da sabiduría y paciencia. Virgen del Milagro, tú que conoces la vida sencilla, acompaña nuestro esfuerzo de cada día. Amén." },
    { t: "Por los enfermos", f: "Que Dios consuele y sane.", p: "Señor Jesús, médico de los cuerpos y de las almas, te presento a los enfermos, a los que esperan un diagnóstico y a los que cuidan de ellos con tanto amor. Dales alivio en el dolor, fuerza en la espera y paz en el corazón. Bendice las manos de médicos y enfermeras. Si es tu voluntad, devuélveles la salud; y en todo caso, acompáñalos de cerca. Virgen del Milagro, salud de los enfermos, ruega por ellos. Amén." },
    { t: "Por mis hijos", f: "Confía a Dios a quienes más amas.", p: "Señor, te encomiendo a mis hijos, que son tu regalo. Cuídalos cuando no puedo hacerlo, protégelos de los peligros del camino y de las malas compañías. Dales un corazón limpio, ganas de estudiar y valor para ser buenas personas. Y a mí dame paciencia para guiarlos con amor y buen ejemplo. Madre del Milagro, tú que también cuidaste a tu Hijo, cuida de los míos todos los días de su vida. Amén." },
    { t: "Por mis padres y abuelos", f: "Gracias por quienes nos dieron la vida.", p: "Dios de bondad, gracias por mis padres y mis abuelos, por su sacrificio y por todo lo que me enseñaron. Bendice su vejez, cuida su salud y llena sus días de compañía y de paz. Si ya partieron, recíbelos en tu casa y dales el descanso eterno. Ayúdame a honrarlos con mi vida y a no dejar para mañana el abrazo que hoy puedo dar. Virgen del Milagro, acompaña a nuestros mayores. Amén." },
    { t: "Confío en ti, Señor", f: "Entrega hoy lo que no puedes controlar.", p: "Señor, hay cosas que no puedo resolver ni controlar, y por eso las pongo en tus manos. Te entrego mis planes, mis dudas y mis preocupaciones. Enséñame a hacer lo que me toca y a confiar en que tú harás lo demás. Que mi corazón descanse sabiendo que tu amor nunca me abandona. Santa María, Virgen del Milagro, tú que dijiste «hágase» con confianza, enséñame a decir lo mismo. Amén." },
    { t: "Un corazón humilde", f: "La grandeza empieza por servir.", p: "Jesús, manso y humilde de corazón, líbrame del orgullo y de la necesidad de tener siempre la razón. Enséñame a escuchar, a reconocer mis errores y a servir sin esperar aplausos. Que no me compare con nadie ni busque ser más que los demás, sino ser fiel a lo que tú me pides. Madre del Milagro, esclava del Señor, enséñame la sencillez con que viviste y confiaste en Dios. Amén." },
    { t: "Calma para la ansiedad", f: "Respira: Dios sostiene tu día.", p: "Señor, mi mente da vueltas y mi pecho está apretado por lo que podría pasar. Ayúdame a respirar, a vivir un solo día a la vez y a recordar que tú ya estás en mi mañana. Aleja los pensamientos que me roban la paz y dame serenidad para hacer lo que está en mis manos. Si necesito ayuda, dame humildad para buscarla. Virgen del Milagro, consuelo de los afligidos, quédate conmigo. Amén." },
    { t: "Por Tunja y Boyacá", f: "Bendice, Madre, a tu pueblo.", p: "Dios de todos, bendice a Tunja y a toda Boyacá: a sus familias, a sus campesinos, a sus estudiantes, a sus comerciantes y a sus gobernantes. Que en nuestras calles y veredas haya trabajo digno, respeto y solidaridad. Protege nuestras montañas, nuestras aguas y nuestra gente. Virgen del Milagro, patrona de esta tierra, sigue velando por nosotros como lo has hecho durante tantos años y mantén viva nuestra fe. Amén." },
    { t: "Consuelo en el duelo", f: "Dios acompaña a quien llora.", p: "Señor de la vida, hoy lloro por quien ya no está. Tú conoces mi dolor y no me pides que lo esconda. Consuélame con tu cercanía, abraza a mi familia y recibe en tu paz a nuestro ser querido. Dame fuerza para seguir y esperanza para volver a sonreír sin sentirme culpable. Creo que el amor es más fuerte que la muerte. Virgen del Milagro, Madre dolorosa, acompáñame en este camino. Amén." },
    { t: "Una fe que no se apaga", f: "Aunque dudes, Dios te sostiene.", p: "Señor, creo; ayuda mi poca fe. Hay días en que dudo y siento que mi oración no llega. Aun así, vuelvo a ti, porque no tengo a quién más acudir. Aumenta mi confianza, mantén encendida mi lámpara y hazme perseverante en la oración, en los sacramentos y en el amor al prójimo. Que mi fe se note en lo que hago. Madre del Milagro, tú que creíste sin ver, intercede por mí. Amén." },
    { t: "Por Colombia", f: "Que reine la paz en nuestra patria.", p: "Dios de la paz, mira con compasión a Colombia. Sana las heridas de la violencia, consuela a las víctimas y convierte el corazón de quienes hacen daño. Que haya justicia sin venganza, trabajo para todos y oportunidades para los jóvenes. Enséñanos a dialogar, a cuidarnos y a construir juntos un país reconciliado. Virgen del Milagro, Madre de todos los colombianos, ruega por nuestra patria y por quienes la gobiernan. Amén." },
    { t: "Alegría de vivir", f: "La alegría de Dios es tu fuerza.", p: "Señor, gracias por las pequeñas alegrías: un café, una risa, una conversación, el sol de la mañana. Ayúdame a verlas y a no vivir solo esperando lo que falta. Que mi alegría sea sencilla y contagiosa, y que sepa compartirla con quien hoy está triste. Líbrame de la queja constante y enséñame a cantar por dentro, aun en medio de las dificultades. Virgen del Milagro, causa de nuestra alegría, acompáñame hoy. Amén." },
    { t: "Luz para decidir", f: "Pide sabiduría antes de dar el paso.", p: "Espíritu Santo, estoy frente a una decisión importante y no sé qué camino tomar. Ilumina mi mente, calma mis emociones y muéstrame con claridad lo que es bueno ante ti. Dame valor para elegir lo correcto aunque cueste, y humildad para pedir consejo a quien sabe. Que no me dejen llevar la prisa ni el miedo. Haz que, decida lo que decida, permanezca en tu voluntad. Madre del Milagro, buena consejera, guíame. Amén." },
    { t: "Por los que están lejos", f: "Dios cuida a quien vive en tierra extraña.", p: "Señor, te pido por los que están lejos de casa: migrantes, trabajadores y estudiantes que extrañan a su familia y su tierra. Protégelos en el camino, dales trabajo justo y gente buena a su lado. Alivia la nostalgia, cuida a los que dejaron atrás y acorta las distancias con tu amor. Que un día puedan abrazarse de nuevo. Virgen del Milagro, tú que conoces el camino del exilio, acompaña a todos los que viven lejos. Amén." },
    { t: "Sana mi corazón", f: "Dios cura las heridas que nadie ve.", p: "Jesús, tú que curaste a tantos enfermos, sana también mi corazón. Hay heridas del pasado que todavía duelen: palabras que me lastimaron, abandonos y fracasos. Pasa tu mano sobre ellas y devuélveme la paz. Enséñame a no vivir atado a lo que ya fue y a abrirme de nuevo al amor y a la confianza. Dame la gracia de comenzar de nuevo hoy mismo. Madre del Milagro, ternura de Dios, abrázame. Amén." },
    { t: "Un corazón generoso", f: "Dar es la mejor manera de recibir.", p: "Padre bueno, tú nos lo has dado todo. Hazme generoso con mi tiempo, mi atención y lo poco o mucho que tengo. Abre mis ojos para ver al que necesita ayuda y mis manos para dar sin humillar. Líbrame de la avaricia y del miedo a que me falte. Que mi vida sea un pan compartido para otros. Virgen del Milagro, que fuiste a servir a tu prima Isabel sin demora, enséñame a servir con alegría. Amén." },
    { t: "Paciencia", f: "Todo llega a su tiempo.", p: "Señor, hoy me pides paciencia y a mí me cuesta esperar. Paciencia con las personas que me desesperan, con los procesos que se demoran y conmigo mismo cuando tropiezo de nuevo. Enséñame que tú trabajas en silencio y que lo bueno se construye despacio. Quita de mi boca la palabra dura y de mi corazón la irritación. Dame la calma de quien sabe que está en tus manos. Virgen del Milagro, Madre paciente, ruega por mí. Amén." },
    { t: "Por los jóvenes y estudiantes", f: "Que Dios bendiga sus sueños.", p: "Dios de la sabiduría, bendice a los jóvenes y a quienes estudian. Dales claridad para aprender, constancia para no rendirse y ánimo cuando el cansancio aprieta. Ayúdalos a elegir buenas amistades, a cuidar su cuerpo y su corazón, y a descubrir para qué estás llamándolos. Que sus sueños se hagan realidad y sirvan para el bien de todos. Virgen del Milagro, madre y maestra, acompaña cada examen, cada decisión y cada paso de su futuro. Amén." },
    { t: "Por los matrimonios", f: "El amor se cuida cada día.", p: "Señor, bendice a los esposos y a las parejas. Ayúdalos a escucharse, a pedirse perdón y a elegirse de nuevo cada día, también cuando hay cansancio y dificultades. Protege su amor de la rutina, del orgullo y de lo que quiere dividirlos. Que sepan reír juntos, rezar juntos y sostenerse en los momentos duros. Que sus hijos vean en ellos un reflejo de tu amor. Virgen del Milagro, Madre del amor hermoso, cuida cada hogar. Amén." },
    { t: "Cuando me siento solo", f: "Dios está más cerca de lo que piensas.", p: "Señor, hoy me siento solo, aunque haya gente a mi alrededor. Tú que conoces cada rincón de mi corazón, hazte presente. Que sienta tu compañía en el silencio y que encuentre personas buenas con quienes compartir la vida. Dame también ánimo para acercarme, llamar y tender la mano a otro que esté igual. Que mi soledad se convierta en un lugar de encuentro contigo. Madre del Milagro, tú nunca me dejas, quédate a mi lado. Amén." },
    { t: "Oración de la noche", f: "Entrega el día y descansa en paz.", p: "Señor, termina el día y te doy gracias por todo lo vivido: lo bueno y lo difícil. Perdona mis faltas, mis palabras duras y lo que dejé de hacer. Pongo en tus manos a mi familia, a mis amigos y a quienes sufren esta noche. Cuida mi sueño y que mañana despierte con nuevas fuerzas. Bajo el manto de la Virgen del Milagro me acuesto en paz, porque tú velas por mí. Dios te salve, María. Amén." },
    { t: "Bendice mi hogar", f: "Que Dios habite en tu casa.", p: "Dios de amor, bendice esta casa y a todos los que viven en ella. Que no falten el pan, la alegría ni la paz. Aleja de aquí las discusiones, la envidia y todo mal. Haz de nuestro hogar una pequeña iglesia, donde se ora, se perdona y se ama. Que quien llegue a nuestra puerta encuentre acogida y descanso. Virgen del Milagro, protectora de los hogares, ocupa un lugar en nuestra mesa y en nuestro corazón. Amén." },
    { t: "Hágase tu voluntad", f: "Entrégate a Dios sin miedo.", p: "Señor, aquí estoy. Quiero hacer tu voluntad aunque no la entienda del todo. Toma mi vida con sus luces y sombras y úsala como tú quieras. Que mis planes se alineen con los tuyos y que mi vida diga a otros que eres bueno. Dame la sencillez de María para decir «sí» y la confianza para dar cada paso. Virgen del Milagro, muéstranos a Jesús y enséñanos a hacer lo que él nos diga. Amén." }
  ];
  var MOODS = [
    ["#BBD9F5", "#FFF3D6", "#FFF1B8"],
    ["#F6D3DC", "#FFF2E2", "#FFE2C2"],
    ["#FBE5AC", "#FFF8E4", "#FFEFB0"],
    ["#A9CFF2", "#E8F2FB", "#FFF4CC"],
    ["#D9D0F0", "#FDEFF6", "#FFEFD2"],
    ["#CBE6D2", "#FFF7DF", "#FFF3BE"]
  ];

  var MESES = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
  var DIAS = ["domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado"];
  function dayNumber(d) { return Math.floor((Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()) - Date.UTC(d.getFullYear(), 0, 0)) / 86400000); }
  /* Oraciones a la Virgen María (textos tradicionales) y una propia; una distinta cada día */
  var MARIAS = [
    { t: "Oración a la Virgen del Milagro", p: "Virgen del Milagro, Madre de Dios y Madre nuestra, patrona de Tunja: hoy vengo a ponerme bajo tu manto. Tú conoces mis alegrías y mis penas, lo que digo y lo que callo. Preséntale a Jesús mis necesidades y las de mi familia, y alcánzame la gracia de confiar, de perdonar y de amar. Cuida nuestra ciudad, nuestros hogares y a todos los que sufren. Que cada día de mi vida sea un pequeño milagro de tu Hijo. Amén." },
    { t: "Bajo tu amparo", p: "Bajo tu amparo nos acogemos, santa Madre de Dios; no desprecies las súplicas que te dirigimos en nuestras necesidades, antes bien líbranos de todo peligro, oh Virgen gloriosa y bendita. Amén." },
    { t: "Acordaos", p: "Acordaos, oh piadosísima Virgen María, que jamás se ha oído decir que ninguno de los que han acudido a vuestra protección, implorando vuestra asistencia y reclamando vuestro socorro, haya sido abandonado de vos. Animado por esta confianza, a vos acudo, oh Madre, Virgen entre las vírgenes; a vos vengo, y gimiendo bajo el peso de mis pecados me atrevo a comparecer ante vuestra presencia soberana. No desechéis mis súplicas, oh Madre del Verbo divino; antes bien, escuchadlas y acogedlas benignamente. Amén." }
  ];
  /* Siempre al final, completas: Ave María y Salve */
  var FIJAS = [
    { t: "Dios te salve, María (Ave María)", p: "Dios te salve, María, llena eres de gracia, el Señor es contigo; bendita tú eres entre todas las mujeres, y bendito es el fruto de tu vientre, Jesús. Santa María, Madre de Dios, ruega por nosotros, pecadores, ahora y en la hora de nuestra muerte. Amén." },
    { t: "Dios te salve, Reina y Madre (Salve)", p: "Dios te salve, Reina y Madre de misericordia, vida, dulzura y esperanza nuestra; Dios te salve. A ti llamamos los desterrados hijos de Eva; a ti suspiramos, gimiendo y llorando en este valle de lágrimas. Ea, pues, Señora, abogada nuestra, vuelve a nosotros esos tus ojos misericordiosos; y después de este destierro, muéstranos a Jesús, fruto bendito de tu vientre. ¡Oh clemente, oh piadosa, oh dulce Virgen María! Ruega por nosotros, santa Madre de Dios, para que seamos dignos de alcanzar las promesas de nuestro Señor Jesucristo. Amén." }
  ];
  var NUEVAS = {
    N1: { t: "Mi vida es de Dios", f: "Ofrécele hoy tu día al Señor.", p: "Señor, hoy quiero ofrecerte mi vida completa: mis planes, mi trabajo, mis cansancios y mis alegrías. No quiero guardarme nada. Haz de mí lo que quieras y úsame para el bien de los demás. Que mi pequeña vida sea una ofrenda agradable ante ti. Y cuando me sienta lejos, recuérdame que desde siempre me perteneces y que me cuidas. Amén." },
    N2: { t: "Cristo, Rey de mi vida", f: "Que Jesús reine en tu casa.", p: "Señor Jesús, Rey del universo, tú reinas sirviendo y amando, no con poder ni con fuerza. Reina en mi corazón, en mi familia, en mi trabajo y en mi país. Quita de mí el orgullo y la ambición y enséñame a servir. Que mis decisiones sean tuyas y que, al final de mi camino, pueda contemplarte con alegría. Venga a nosotros tu reino de justicia, de paz y de amor. Amén." },
    N3: { t: "Líbranos del mal", f: "Dios te cubre en la tentación.", p: "Señor, conoces mis debilidades y los lugares donde caigo con facilidad. No me dejes caer en la tentación y líbrame del mal: de la mentira, del rencor, de los vicios y de las malas compañías. Dame fuerza para decir no cuando haga falta y humildad para pedir ayuda. Que tu gracia sea mi escudo cada día y que, si caigo, me levante pronto con tu perdón. Amén." },
    N4: { t: "Empieza el Adviento", f: "Esperar con el corazón despierto.", p: "Señor Jesús, empieza el tiempo de esperarte. Despierta mi corazón dormido por la rutina y las prisas. Ayúdame a preparar espacio para ti: un rato de silencio, una oración, una buena obra, un perdón. Que no viva distraído por el ruido de la Navidad, sino atento a tu llegada. Ven, Señor Jesús, y quédate en mi casa. Amén." },
    N5: { t: "Preparar el camino", f: "Haz sitio para Jesús.", p: "Dios de la esperanza, quiero preparar en mi vida el camino de tu Hijo. Ayúdame a enderezar lo torcido, a rebajar mi orgullo y a limpiar lo que me estorba. Reconcilia lo que está roto en mi familia y en mi corazón. Que estos días sean de conversión sincera, de generosidad y de oración, para que el Niño Dios encuentre en mí un lugar acogedor. Amén." },
    N6: { t: "Una velita por la Virgen", f: "Enciende una luz y reza.", p: "Señor, esta noche encendemos velitas para alumbrar el camino a la Virgen y a tu Hijo. Que cada llama sea una oración: por mi familia, por mis amigos, por los que sufren y por los que han partido. Que la luz de las velitas me recuerde que tú eres la luz que no se apaga. Bendice a los hogares que hoy se reúnen a rezar, a compartir y a esperar con alegría. Amén." },
    N7: { t: "La Inmaculada Concepción", f: "Dios preparó a María para su Hijo.", p: "Dios, Padre santo, tú preservaste a María del pecado desde el primer instante de su vida, para que fuera digna madre de tu Hijo. Hoy te doy gracias por este regalo para toda la humanidad. Límpiame de mis culpas, renueva mi corazón y dame el deseo sincero de vivir en tu gracia. Que, imitando a María, diga cada día «sí» a tu voluntad. Amén." },
    N8: { t: "Madre de todos los pueblos", f: "Guadalupe, ternura para América.", p: "Señor, en la Virgen de Guadalupe nos regalaste una Madre cercana, que se queda con los pobres y sencillos. Te pido por todos los pueblos de América: por los que sufren pobreza, violencia y olvido. Que encuentren en María consuelo y esperanza, y que todos, sin distinción, nos reconozcamos hermanos. Haznos solidarios y justos, y mantén viva nuestra fe. Amén." },
    N9: { t: "Luz para mi camino", f: "Dios ilumina tus pasos.", p: "Señor, tú eres la luz del mundo. Hoy te pido luz para mis ojos, para ver con claridad lo bueno y lo malo; luz para mi mente, para decidir con sabiduría; y luz para mi corazón, para amar sin miedo. Que la luz de tu Palabra guíe mis pasos y que, a mi vez, yo sea luz para quien camina en tinieblas. Amén." },
    N10: { t: "Comienza la Novena", f: "Nueve días para esperar al Niño.", p: "Niño Dios, comenzamos la Novena de Aguinaldos para esperar tu llegada. Reúne a mi familia, a mis vecinos y a mis amigos para rezar y cantar. Que estos nueve días sean de alegría sencilla, de reconciliación y de buenas obras. Prepara mi corazón para que, cuando llegues, te reciba con amor. Ven, Señor Jesús, no tardes más. Amén." },
    N11: { t: "Un Niño nos ha nacido", f: "La alegría está por llegar.", p: "Dios de amor, nos prometiste un Salvador y estás cumpliendo tu promesa. Gracias porque te haces niño para estar cerca de nosotros. Quita de mi vida la tristeza que me paraliza y dame la alegría de quien espera algo grande. Que mi casa se llene de cantos, de perdón y de gestos de cariño en estos días. Ven pronto, Niño Dios. Amén." },
    N12: { t: "Un hijo en camino", f: "María esperó con amor.", p: "Señor, hoy pienso en María esperando a su Hijo con amor y confianza. Bendice a las madres que esperan un bebé: dales salud, tranquilidad y apoyo. Bendice también a todos los que esperan algo: un trabajo, una noticia, una sanación, un reencuentro. Enséñanos a esperar sin desesperarnos, sabiendo que cada espera está en tus manos. Amén." },
    N13: { t: "José, el hombre justo", f: "Fiel y callado, protegió a Jesús.", p: "Señor, gracias por José, el hombre justo que cuidó a María y a Jesús en silencio. Bendice a los padres de familia y a quienes hacen de padres: dales trabajo digno, paciencia y ternura. Enséñales a proteger a los suyos y a confiar en ti cuando no entienden. Que todos aprendamos de José a obedecerte con sencillez y a cumplir cada día con nuestro deber. Amén." },
    N14: { t: "Un lugar para el Niño", f: "Abre tu casa y tu corazón.", p: "Señor, en Belén no hubo lugar para tu Hijo en la posada. Hoy te pido que no me cierre a nadie: ni al vecino, ni al extranjero, ni al que está solo. Haz que mi casa y mi corazón sean un lugar acogedor, donde cualquiera pueda sentirse recibido. Cuida a las personas sin techo y a los que viajan, y que nadie pase frío esta Navidad. Amén." },
    N15: { t: "La sencillez de Belén", f: "Dios nace en lo pequeño.", p: "Niño Dios, naciste en un pesebre pobre y no en un palacio. Enséñame que la verdadera riqueza no está en lo que tengo sino en el amor con que vivo. Líbrame de la prisa por comprar y aparentar, y regálame ojos para ver tu presencia en lo sencillo: una mirada, un abrazo, un plato compartido. Que mi Navidad sea humilde, alegre y llena de ti. Amén." },
    N16: { t: "Los pastores", f: "Los sencillos llegan primero.", p: "Señor, los pastores fueron los primeros en recibir la noticia de tu nacimiento. Hazme sencillo para escucharte y dispuesto a ir hasta ti sin demora. Que, como ellos, regrese a mi trabajo y a mi casa alabándote por lo que he visto y oído. Bendice a los campesinos, a los trabajadores del campo y a quienes cuidan lo ajeno con honradez. Amén." },
    N17: { t: "La estrella que guía", f: "Dios te muestra el camino.", p: "Señor, una estrella condujo a los Magos hasta tu Hijo. Cuando me sienta perdido, muéstrame la luz que me lleve a ti. Dame perseverancia para buscarte aunque el camino sea largo y valentía para cambiar de ruta si hace falta. Que, al encontrarte, te ofrezca lo mejor de mí: mi tiempo, mi trabajo y mi amor. Amén." },
    N18: { t: "Nochebuena", f: "Esta noche nace la paz.", p: "Niño Dios, esta noche nos reunimos para recibirte. Bendice la mesa, bendice a los que están presentes y a los que faltan. Consuela a quienes pasan esta noche solos, enfermos o de luto, y haz que alguien les haga compañía. Que la paz de Belén entre en cada hogar de Colombia y que nuestro corazón sea tu pesebre. Feliz Nochebuena. Amén." },
    N19: { t: "Nació Jesús", f: "Dios con nosotros.", p: "Señor Jesús, hoy celebramos que Dios se hizo uno de nosotros. Gracias porque no estás lejos, sino a nuestro lado, en lo alegre y en lo difícil. Naces hoy en mi corazón: quédate para siempre. Regálanos tu paz, reconcilia a las familias distanciadas y alegra a los que sufren. Que este día sea de gratitud, de perdón y de abrazos sinceros. ¡Feliz Navidad! Amén." },
    N20: { t: "La Sagrada Familia", f: "Nazaret es modelo para tu hogar.", p: "Señor, gracias por la Sagrada Familia de Nazaret, ejemplo de amor sencillo, trabajo y oración. Bendice a mi familia: que en nuestra casa haya respeto, diálogo y perdón. Ayuda a los que viven conflictos, a las familias separadas y a los que están lejos. Que, como José, María y Jesús, nos apoyemos en las dificultades y compartamos la fe en lo cotidiano. Amén." },
    N21: { t: "Por los niños inocentes", f: "Cuida a los más pequeños.", p: "Señor, hoy recordamos a los niños inocentes que sufrieron la violencia. Te pido por todos los niños que hoy padecen hambre, abandono, maltrato o guerra. Protégelos, rodéalos de personas que los quieran y despierta nuestra conciencia para defenderlos. Convierte el corazón de quienes les hacen daño. Que cada niño de Colombia crezca seguro, amado y con esperanza. Amén." },
    N22: { t: "Gracias por este año", f: "Termina un año; Dios sigue contigo.", p: "Señor, termina este año y quiero darte gracias por todo: por lo logrado, por lo aprendido y también por lo que me dolió, porque me hizo crecer. Perdóname por lo que hice mal o dejé de hacer. Te entrego a mi familia, mis proyectos y mis temores del año que viene. Dame un corazón nuevo para comenzar con fe y esperanza. Bendice a Colombia y a Tunja. Amén." }
  };
  /* Calendario hasta el 31 de diciembre: fecha → tema (número = oración de la lista, N = tema nuevo) y su oración a la Virgen */
  var PLAN = [["11-11", 0], ["11-12", 1], ["11-13", 2], ["11-14", 6], ["11-15", 10], ["11-16", 12], ["11-17", 7], ["11-18", 8], ["11-19", 9], ["11-20", 3], ["11-21", "N1"], ["11-22", "N2"], ["11-23", 5], ["11-24", 13], ["11-25", 23], ["11-26", 17], ["11-27", "N3"], ["11-28", 18], ["11-29", "N4"], ["11-30", 4], ["12-01", "N5"], ["12-02", 11], ["12-03", 21], ["12-04", 22], ["12-05", 19], ["12-06", 24], ["12-07", "N6"], ["12-08", "N7"], ["12-09", 20], ["12-10", 15], ["12-11", 14], ["12-12", "N8"], ["12-13", "N9"], ["12-14", 16], ["12-15", 25], ["12-16", "N10"], ["12-17", "N11"], ["12-18", "N12"], ["12-19", "N13"], ["12-20", "N14"], ["12-21", "N15"], ["12-22", "N16"], ["12-23", "N17"], ["12-24", "N18"], ["12-25", "N19"], ["12-26", 27], ["12-27", "N20"], ["12-28", "N21"], ["12-29", 28], ["12-30", 26], ["12-31", "N22"]];
  var MARIAS_DIA = [
    "Virgen del Milagro, tu alma proclamó la grandeza del Señor y tu espíritu se alegró en Dios. Enséñame a dar gracias como tú: por lo grande y por lo pequeño. Que hoy reconozca los milagros sencillos de mi vida y los comparta con gratitud. Amén.",
    "Madre del Milagro, Reina de la paz, tú guardabas todo en tu corazón y descansabas en Dios. Cubre mi casa y mi mente con tu manto. Que donde haya discusión yo lleve calma y donde haya miedo, confianza. Amén.",
    "Virgen del Milagro, tú guardaste a la Sagrada Familia en la sencillez de Nazaret. Cuida a mi familia como cuidaste a la tuya: une a los que están distantes, sana las diferencias y bendice nuestra mesa. Amén.",
    "Madre del Milagro, tú conoces el trabajo humilde de cada día en la casa de Nazaret. Bendice las manos que trabajan, protege a quienes buscan empleo y no permitas que a ninguna familia le falte el pan. Amén.",
    "Virgen del Milagro, mujer de fe, dijiste «hágase» sin saberlo todo. Enséñame a confiar cuando no entiendo y a descansar en la providencia de tu Hijo. Amén.",
    "Madre del Milagro, consuelo de los afligidos, toma mi mano cuando mi pecho se aprieta. Enséñame a respirar, a vivir el hoy y a recordar que tú me cuidas como a un niño pequeño. Amén.",
    "Virgen del Milagro, salud de los enfermos, mira a quienes hoy sufren en una cama o en una sala de espera. Alivia su dolor, fortalece a sus familias y guía a quienes los cuidan. Amén.",
    "Madre del Milagro, tú cuidaste a Jesús desde niño. Cuida a mis hijos: guárdalos del mal, ilumina su camino y enséñales a amar a Dios. Dame paciencia y buen ejemplo para guiarlos. Amén.",
    "Virgen del Milagro, Madre de todos, bendice a nuestros abuelos y a nuestros padres. Dales salud, compañía y paz, y ayúdanos a honrarlos con cariño y con gestos concretos. Amén.",
    "Madre del Milagro, tú te mantuviste de pie junto a la cruz. Cuando mi prueba me supere, sostenme como sostuviste a Jesús. Dame fuerza para no rendirme y fe para esperar la mañana. Amén.",
    "Virgen del Milagro, desde niña te ofreciste a Dios por entero. Enséñame a presentar mi vida al Señor cada mañana, con mis planes y mis cansancios, para que él la use como quiera. Amén.",
    "Madre del Milagro, tú nos entregas a Jesús como Rey de paz y de amor. Ayúdame a que él reine en mi casa, en mi trabajo y en mi corazón, y a servirle con humildad. Amén.",
    "Virgen del Milagro, Madre de misericordia, tú estuviste junto a la cruz sin odiar a nadie. Enséñame a soltar el rencor y a pedir perdón con sencillez. Ablanda mi corazón y repara mis relaciones. Amén.",
    "Virgen del Milagro, patrona de Tunja, mira a tu ciudad y a toda Boyacá. Bendice a nuestras familias, campos y calles, y sigue velando por tu pueblo como lo has hecho por generaciones. Amén.",
    "Madre del Milagro, tú, joven de Nazaret, dijiste sí al plan de Dios. Acompaña a los jóvenes y estudiantes: dales claridad, constancia y buenos amigos, y ayúdalos a descubrir su vocación. Amén.",
    "Virgen del Milagro, causa de nuestra alegría, tu Magníficat es un canto de gozo. Enséñame a alegrarme en Dios, a ver lo bueno de cada día y a contagiar alegría a los que me rodean. Amén.",
    "Madre del Milagro, Virgen vencedora del mal, cúbreme con tu manto cuando me asalte la tentación. Dame fuerza para decir no al mal y sí a lo bueno, y líbrame de todo peligro del alma. Amén.",
    "Virgen del Milagro, buena consejera, tú ponderabas todo en tu corazón antes de actuar. Ruega por mí para que decida con calma y rectitud, y para que mis pasos sigan lo que Dios quiere. Amén.",
    "Madre del Milagro, tú esperaste a Jesús con amor. Comienza el Adviento: enséñame a esperar despierto, con el corazón preparado y las manos abiertas a los demás. Amén.",
    "Virgen del Milagro, estrella de la esperanza, guía a quienes caminan en la oscuridad. Que no me rinda ni me amargue; que espere con confianza lo que Dios tiene preparado. Amén.",
    "Virgen del Milagro, tú preparaste tu corazón para recibir al Señor. Ayúdame a preparar mi vida en estos días: a ordenar lo que me ata y a hacer sitio para Jesús. Amén.",
    "Madre del Milagro, esclava del Señor, enséñame tu sencillez. Líbrame del orgullo, enséñame a servir en silencio y a alegrarme del bien de los demás. Amén.",
    "Virgen del Milagro, tú fuiste con prontitud a servir a tu prima Isabel. Hazme generoso con mi tiempo y con lo que tengo, y abre mis ojos al que necesita una mano. Amén.",
    "Madre del Milagro, tú esperaste en silencio la hora de Dios. Enséñame paciencia con las personas, con los procesos que tardan y conmigo mismo. Amén.",
    "Virgen del Milagro, tú conociste el camino del exilio. Acompaña a quienes viven lejos de su tierra y de los suyos: protégelos, dales trabajo justo y acorta distancias con tu ternura. Amén.",
    "Madre del Milagro, esposa de José, bendice a los matrimonios. Que se escuchen, se perdonen y se elijan cada día; que Jesús sea el centro de su hogar. Amén.",
    "Virgen del Milagro, esta noche encendemos una velita en tu honor. Que su luz sea oración: alumbra a mi familia, a mi ciudad y a todos los que hoy están en la oscuridad. Amén.",
    "Virgen Inmaculada, llena de gracia desde tu concepción, enséñame a vivir con corazón limpio. Intercede para que yo pueda decir sí a Dios, como tú, y vivir con pureza de intención. Amén.",
    "Madre del Milagro, tú que conoces el dolor, toma mis heridas en tu manto. Pide a Jesús que las sane y devuélveme la capacidad de confiar y de amar. Amén.",
    "Virgen del Milagro, tú creíste que se cumpliría lo dicho por el Señor. Aumenta mi fe cuando dudo, sostén mi oración y hazme perseverante. Amén.",
    "Madre dolorosa del Milagro, tú sostuviste a tu Hijo muerto y esperaste la resurrección. Acompaña a quienes lloran a un ser querido; consuélalos y dales esperanza. Amén.",
    "Virgen de Guadalupe, Madre de los pueblos, tú te quedas junto a los sencillos. Cobija a América Latina y a Colombia con tu ternura y enséñanos a vivir como hermanos. Amén.",
    "Virgen del Milagro, Madre de la luz, ilumina mis ojos y mi alma. Que vea con claridad lo que Dios me muestra y que mi vida sea luz para otros. Amén.",
    "Virgen del Milagro, Madre de todos los colombianos, intercede por nuestra patria. Que cesen la violencia y el odio y crezcan la paz, la justicia y la reconciliación. Amén.",
    "Madre del Milagro, tú nunca me dejas solo. Hazte presente en mi silencio, acompáñame con tu ternura y abre mi camino hacia personas buenas. Amén.",
    "Virgen del Milagro, comienza la Novena de Aguinaldos. Contigo esperamos al Niño Dios: enséñame a prepararme con oración, alegría y buenas obras. Amén.",
    "Madre del Milagro, un Niño nos ha nacido. Dame un corazón alegre y sencillo para recibirlo, y que mi casa sea un pesebre acogedor. Amén.",
    "Virgen del Milagro, tú esperaste con amor la hora de dar a luz. Acompaña a las madres que esperan y a todos los que aguardan con esperanza. Amén.",
    "Madre del Milagro, esposa del justo José, bendice a los padres de familia. Que sean fieles, trabajadores y tiernos como José, y cuiden a los suyos en silencio. Amén.",
    "Virgen del Milagro, para ti no hubo lugar en la posada. Abre mi corazón y mi casa a quien no tiene techo ni lugar, y haz que nadie quede sin acogida. Amén.",
    "Madre del Milagro, en un pesebre pobre acostaste al Rey del universo. Enséñame a amar la sencillez, a no depender de lo material y a descubrir a Dios en lo pequeño. Amén.",
    "Virgen del Milagro, los pastores fueron los primeros en llegar. Ayúdame a ser sencillo para encontrar a Jesús y a anunciar con alegría lo que he visto y oído. Amén.",
    "Madre del Milagro, una estrella guió a los Magos hasta tu Hijo. Sé tú la estrella de mi camino y guíame hasta Jesús cuando me sienta perdido. Amén.",
    "Virgen del Milagro, esta noche pones en el pesebre al Niño Dios. Déjame llegar contigo a Belén y adorarlo; que la paz de esta noche habite todos los hogares. Amén.",
    "Madre del Milagro, hoy tienes en tus brazos al Salvador del mundo. Preséntalo a mi corazón; que su paz y su alegría llenen mi casa y mi vida. Amén.",
    "Virgen del Milagro, protectora de los hogares, ocupa un lugar en nuestra mesa. Que en nuestra casa haya perdón, alegría y oración después de la fiesta. Amén.",
    "Madre del Milagro, Madre de la Sagrada Familia, haz de mi familia un pequeño Nazaret, donde se trabaja, se reza y se ama con sencillez. Amén.",
    "Virgen del Milagro, dolorosa por los inocentes, cuida a los niños que sufren hambre, abandono o violencia. Protégelos y despierta nuestra conciencia para defenderlos. Amén.",
    "Madre del Milagro, tu «hágase» cambió la historia. Enséñame a decir sí a lo que Dios me pida, con confianza y sin miedo. Amén.",
    "Virgen del Milagro, al terminar este día me pongo bajo tu manto. Cuida mi sueño, perdona mis faltas y acompáñame hasta el amanecer. Amén.",
    "Madre del Milagro, termina el año y te doy gracias contigo. Presenta a Jesús lo vivido, lo logrado y lo que no pude; y acompáñame en el año que viene con tu ternura. Amén."
  ];
  /* Tarde y noche por día (mismo orden que PLAN): [título imagen tarde, cita, versículo resumido, reflexión, título imagen noche, oración de la noche] */
  var TN = [
 [
  "Este día lo hizo el Señor",
  "Salmo 118,24",
  "Este es el día que hizo el Señor: alegrémonos y gocémonos en él.",
  "Aunque el día no salga perfecto, sigue siendo un regalo. ¿Por qué cosa pequeña puedes dar gracias ahora mismo?",
  "Gracias por este día",
  "Señor, al cerrar el día te doy gracias por lo bueno y por lo que me enseñó lo difícil. Perdona mis faltas y recibe mi cansancio. Descansaré en paz, porque tú me sostienes. Amén."
 ],
 [
  "Mi paz les dejo",
  "Juan 14,27",
  "Jesús deja su paz a los suyos: no es la paz del mundo, es una paz que calma el corazón.",
  "La paz de Dios no depende de que todo esté resuelto. Respira y entrégale hoy lo que te inquieta.",
  "Duerme en paz",
  "Señor Jesús, pongo en tus manos lo que me quitó la calma hoy. Que tu paz entre en mi casa y en mi pecho, y que mi sueño sea tranquilo. Amén."
 ],
 [
  "Yo y mi casa serviremos al Señor",
  "Josué 24,15",
  "Josué declara que él y su familia servirán al Señor.",
  "Una familia que reza unida se sostiene mejor. Hoy puedes empezar con una sola oración compartida.",
  "Bendice a mi familia esta noche",
  "Padre bueno, esta noche cuida a cada miembro de mi familia: a los que duermen conmigo y a los que están lejos. Aleja el mal de nuestra casa y que amanezcamos unidos. Amén."
 ],
 [
  "Dios bendice tu trabajo",
  "Salmo 90,17",
  "El salmista pide que Dios afirme la obra de nuestras manos.",
  "Tu esfuerzo no pasa desapercibido para Dios. Hazlo con honradez y confía en que Él pondrá lo que falta.",
  "Descansa de tu esfuerzo",
  "Señor, gracias por el trabajo de hoy, aunque haya sido duro. Bendice lo que hice, perdona lo que dejé a medias y no permitas que falte el pan en mi mesa. Amén."
 ],
 [
  "Confía en el Señor",
  "Proverbios 3,5-6",
  "Confía en el Señor con todo el corazón y no te apoyes solo en tu inteligencia; Él enderezará tus caminos.",
  "No tienes que controlarlo todo. Haz lo que te toca y deja el resto en manos de Dios.",
  "Entrégale tu mañana",
  "Señor, esta noche te entrego mis planes y mis dudas. Lo que no pude resolver hoy lo dejo contigo. Descanso confiando en que tú ya estás en mi mañana. Amén."
 ],
 [
  "Entrégale tus preocupaciones",
  "1 Pedro 5,7",
  "Pongan en Dios todas sus preocupaciones, porque Él cuida de ustedes.",
  "Tus preocupaciones pesan menos cuando las compartes con Él. Dile en voz alta lo que te preocupa.",
  "Respira y descansa",
  "Señor, mi mente da vueltas y mi pecho se aprieta. Te entrego lo que me preocupa; tú cuidas de mí. Dame una noche tranquila y un despertar sereno. Amén."
 ],
 [
  "Dios sana y consuela",
  "Salmo 103,3",
  "Dios perdona todas nuestras culpas y sana todas nuestras dolencias.",
  "Si hoy hay alguien enfermo en tu vida, ora por esa persona con nombre propio. Dios escucha.",
  "Por los enfermos esta noche",
  "Señor, esta noche te presento a los enfermos y a quienes los cuidan. Dales alivio en el dolor, calma en la espera y compañía. Que descansen en tu amor. Amén."
 ],
 [
  "Los hijos son un regalo",
  "Salmo 127,3",
  "Los hijos son una herencia del Señor, un premio que viene de Él.",
  "Cuida a tus hijos con tiempo y con ejemplo; una palabra buena hoy puede quedar para siempre.",
  "Bendice a mis hijos",
  "Padre, mientras mis hijos duermen te los encomiendo. Cuídalos de todo mal, bendice sus sueños y dame mañana paciencia y ternura para guiarlos. Amén."
 ],
 [
  "Honra a tu padre y a tu madre",
  "Éxodo 20,12",
  "El mandamiento invita a honrar al padre y a la madre.",
  "Una llamada, una visita o un abrazo valen más que un regalo. No dejes para mañana el cariño que hoy puedes dar.",
  "Gracias por mis mayores",
  "Señor, gracias por mis padres y mis abuelos. Dales salud, compañía y paz esta noche. Enséñame a cuidarlos con amor mientras los tengo. Amén."
 ],
 [
  "Mi gracia te basta",
  "2 Corintios 12,9",
  "Dios dice: mi gracia te basta, mi poder se muestra en la debilidad.",
  "No necesitas ser fuerte todo el tiempo. Dios actúa precisamente donde tú ya no puedes más.",
  "Dios te sostiene",
  "Señor, hoy me pesó la prueba y aun así llegué hasta aquí contigo. Dame descanso esta noche y fuerzas nuevas mañana. No me sueltes de tu mano. Amén."
 ],
 [
  "Ofrece tu vida a Dios",
  "Romanos 12,1",
  "Pablo invita a ofrecer la propia vida como ofrenda viva y agradable a Dios.",
  "Ofrecer tu día no es hacer cosas extraordinarias, sino hacer lo de siempre con amor y para Dios.",
  "Todo lo que hice, para ti",
  "Señor, lo que hice hoy, bueno o torpe, te lo ofrezco. Purifica lo que salió mal y bendice lo que salió bien. Mañana también quiero ser tuyo. Amén."
 ],
 [
  "Jesucristo es el Señor",
  "Filipenses 2,11",
  "Toda lengua proclamará que Jesucristo es Señor, para gloria de Dios Padre.",
  "Ser de Cristo es dejar que Él ordene lo que más quieres. ¿Qué parte de tu vida le entregas hoy?",
  "Reina en mi corazón",
  "Señor Jesús, Rey de paz, reina en mi casa y en mi corazón esta noche. Perdona lo que no fue de ti y guárdame en tu amor hasta el amanecer. Amén."
 ],
 [
  "Perdona como Dios perdona",
  "Colosenses 3,13",
  "Soporten y perdonen a los demás, como el Señor los perdonó.",
  "Perdonar no es olvidar ni justificar; es dejar de cargar con el dolor. Empieza por una oración por esa persona.",
  "Duerme sin rencor",
  "Señor, esta noche quiero soltar el rencor. Perdona mis ofensas y ayúdame a perdonar a quien me hirió. Que mi corazón duerma libre. Amén."
 ],
 [
  "Busquen el bien de la ciudad",
  "Jeremías 29,7",
  "Busquen el bien de la ciudad y oren por ella, porque su bienestar es el de ustedes.",
  "Orar por Tunja es también servirle: un saludo, un gesto de respeto, una ayuda al vecino.",
  "Bendice a Tunja esta noche",
  "Señor, bendice esta noche a Tunja y a Boyacá. Cuida a los que duermen en la calle y a los que velan por nosotros. Que mañana amanezca con paz y trabajo. Amén."
 ],
 [
  "Sé ejemplo para otros",
  "1 Timoteo 4,12",
  "Que nadie te desprecie por ser joven: sé ejemplo en palabra, conducta, amor, fe y pureza.",
  "Ser joven no es un obstáculo para Dios; es una oportunidad para empezar bien. Elige hoy una cosa buena.",
  "Sueños bendecidos",
  "Señor, bendice los sueños de los jóvenes y los estudios de mañana. Dales claridad, constancia y buenas amistades. Que descansen bajo tu cuidado. Amén."
 ],
 [
  "La alegría del Señor es su fuerza",
  "Nehemías 8,10",
  "No estén tristes: la alegría del Señor es la fortaleza de ustedes.",
  "La alegría no es ausencia de problemas, es saber que Dios está contigo en ellos. Regálale una sonrisa a alguien hoy.",
  "Cierra el día con gratitud",
  "Señor, gracias por las pequeñas alegrías de hoy: una risa, una charla, un plato caliente. Que no olvide verlas mañana. Descanso contento en ti. Amén."
 ],
 [
  "Dios te da la salida",
  "1 Corintios 10,13",
  "Dios es fiel: no permite que seamos tentados más allá de lo que podemos soportar y da una salida.",
  "Cuando la tentación aprieta, hay una salida. Pide ayuda a tiempo y aléjate del lugar o de la compañía que te hace caer.",
  "Líbranos del mal",
  "Señor, perdona lo que hoy hice mal y líbrame del mal esta noche. Cuida mi mente y mi corazón y dame fuerza para elegir bien mañana. Amén."
 ],
 [
  "Pide sabiduría a Dios",
  "Santiago 1,5",
  "Si a alguien le falta sabiduría, que se la pida a Dios, que da con generosidad.",
  "Antes de decidir, haz silencio y ora. La prisa casi nunca es buena consejera.",
  "Lo decidiré mañana contigo",
  "Espíritu Santo, esta noche te entrego mis decisiones pendientes. Aclara mi mente mientras duermo y que mañana vea con calma el camino a seguir. Amén."
 ],
 [
  "Estén despiertos",
  "Marcos 13,37",
  "Jesús invita a todos a estar atentos y vigilantes.",
  "Empieza el Adviento. Hoy puedes preparar un espacio: un minuto de silencio, una vela, una oración corta.",
  "Primera noche de Adviento",
  "Señor Jesús, empieza el tiempo de esperarte. Despierta mi corazón y prepáralo para tu llegada. Que descanse esta noche en tu promesa. Amén."
 ],
 [
  "Dios es el Dios de la esperanza",
  "Romanos 15,13",
  "Pablo desea que el Dios de la esperanza llene de alegría y paz a los que creen.",
  "La esperanza no ignora el problema: sabe que Dios es más grande. Espera con confianza.",
  "Mañana vendrá la luz",
  "Dios fiel, termina un día más y aún confío en ti. Enciende en mí la esperanza y que amanezca con ánimo nuevo. Amén."
 ],
 [
  "Preparen el camino del Señor",
  "Isaías 40,3",
  "Una voz grita: preparen en el desierto el camino del Señor.",
  "Preparar el camino es arreglar lo que está torcido en tu vida: una conversación pendiente, un hábito, una deuda de cariño.",
  "Prepara tu corazón",
  "Señor, esta noche reviso mi día y te pido perdón por lo torcido. Endereza mis caminos y haz sitio en mi vida para tu Hijo. Amén."
 ],
 [
  "Quien se humilla será elevado",
  "Mateo 23,12",
  "Jesús dice que quien se humilla será enaltecido.",
  "La humildad no es hablar mal de ti, es servir sin esperar aplausos.",
  "Descansa en la sencillez",
  "Señor, perdona mi orgullo de hoy. Enséñame a servir sin presumir y a alegrarme del bien ajeno. Duermo en tu paz. Amén."
 ],
 [
  "Dios ama al que da con alegría",
  "2 Corintios 9,7",
  "Cada uno dé lo que decidió en su corazón, no con tristeza ni por obligación, porque Dios ama al que da con alegría.",
  "Dar no siempre es dinero: tu tiempo, tu escucha y tu compañía también son regalos.",
  "Dar es recibir",
  "Señor, hazme generoso con lo que tengo, mucho o poco. Que mañana encuentre a alguien a quien ayudar. Bendice a quien hoy dio algo por mí. Amén."
 ],
 [
  "Esperen con paciencia",
  "Santiago 5,7-8",
  "Como el agricultor espera el fruto de la tierra, así esperen con paciencia, porque el Señor está cerca.",
  "Lo bueno crece despacio. No te desesperes: lo que estás sembrando hoy dará fruto a su tiempo.",
  "Paciencia, todo llega",
  "Señor, hoy se me acabó la paciencia varias veces. Perdóname y enséñame a esperar tu tiempo con calma. Duermo confiando en ti. Amén."
 ],
 [
  "Allí también me guiará tu mano",
  "Salmo 139,9-10",
  "Aunque me vaya al extremo del mar, allí me guiará tu mano y me sostendrá tu derecha.",
  "Dios no queda atrás cuando alguien emigra: viaja con cada persona. Acompáñala con una oración.",
  "Por los que están lejos",
  "Señor, esta noche te pido por los que están lejos de casa. Protégelos, dales trabajo justo y que sientan el cariño de los suyos. Amén."
 ],
 [
  "Dos son mejor que uno",
  "Eclesiastés 4,9",
  "Dos valen más que uno solos, porque si uno cae, el otro lo levanta.",
  "Un matrimonio se sostiene con detalles pequeños: un gracias, un perdón a tiempo, un rato para hablar.",
  "Bendice a mi pareja",
  "Señor, bendice esta noche a mi pareja y a nuestro hogar. Danos paciencia para escucharnos, humildad para pedir perdón y alegría para seguir juntos. Amén."
 ],
 [
  "Yo soy la luz del mundo",
  "Juan 8,12",
  "Jesús dice: quien me sigue no caminará en tinieblas, tendrá la luz de la vida.",
  "Esta noche de velitas, enciende una luz y pide por alguien que hoy vive en la oscuridad.",
  "Una velita por todos",
  "Señor, que la luz de esta velita sea mi oración. Alumbra a mi familia, a los que sufren y a los que ya partieron. Gracias, Virgen del Milagro, por acompañarnos. Amén."
 ],
 [
  "Llena de gracia",
  "Lucas 1,28",
  "El ángel saluda a María: alégrate, llena de gracia, el Señor está contigo.",
  "Dios prepara a quien llama. Si hoy sientes que no estás a la altura, Él está contigo.",
  "Gracias, Madre Inmaculada",
  "Señor, gracias por María, preparada desde el principio para ser Madre de tu Hijo. Limpia mi corazón y que, como ella, diga sí a tu voluntad. Amén."
 ],
 [
  "Dios sana a los de corazón herido",
  "Salmo 147,3",
  "Él sana a los de corazón quebrantado y venda sus heridas.",
  "Hay heridas que no se ven, pero Dios las conoce. Déjalo que las cure poco a poco.",
  "Que sane tu corazón",
  "Jesús, pasa tu mano sobre mis heridas esta noche. Quita el dolor del pasado y devuélveme la confianza. Descanso en tu ternura. Amén."
 ],
 [
  "La fe es confiar",
  "Hebreos 11,1",
  "La fe es la seguridad de lo que se espera y la prueba de lo que no se ve.",
  "Creer no es no dudar: es seguir caminando con Dios incluso cuando dudas.",
  "Mi fe sigue encendida",
  "Señor, creo; ayuda mi poca fe. Perdona mis dudas de hoy y mantén encendida mi lámpara. Duermo confiando en ti. Amén."
 ],
 [
  "Dichosos los que lloran",
  "Mateo 5,4",
  "Jesús dice que son dichosos los que lloran, porque recibirán consuelo.",
  "Llorar a quien amas es amar. No tienes que ser fuerte todo el tiempo; Dios abraza tu tristeza.",
  "Consuelo en tu duelo",
  "Señor de la vida, recibe en tu paz a nuestros seres queridos y consuela esta noche a quienes los lloramos. Abraza nuestro dolor. Amén."
 ],
 [
  "No estoy yo aquí, que soy tu madre",
  "Juan 19,26-27",
  "Jesús, desde la cruz, entrega a María como Madre al discípulo.",
  "María nos fue dada como Madre. Hoy puedes acudir a ella como un hijo que busca consuelo.",
  "Madre de todos los pueblos",
  "Virgen de Guadalupe, Madre de América, cuida esta noche a los pobres, a los migrantes y a los olvidados. Cobíjanos a todos bajo tu manto. Amén."
 ],
 [
  "Tu palabra es lámpara",
  "Salmo 119,105",
  "Tu palabra es lámpara para mis pasos y luz en mi camino.",
  "No necesitas ver todo el camino: basta con la luz para el siguiente paso.",
  "Luz para mañana",
  "Señor, ilumina mis pasos de mañana con tu Palabra. Y que mi vida sea un poco de luz para quien hoy camina en tinieblas. Amén."
 ],
 [
  "La justicia y la paz se abrazan",
  "Salmo 85,11",
  "El salmista anuncia que la justicia y la paz se encontrarán.",
  "La paz del país empieza en los pequeños gestos: respeto, diálogo y honestidad en la casa y en la calle.",
  "Paz para Colombia",
  "Señor, bendice esta noche a Colombia. Consuela a las víctimas de la violencia, convierte los corazones y que amanezca un país más reconciliado. Amén."
 ],
 [
  "No te dejaré ni te abandonaré",
  "Deuteronomio 31,6",
  "Dios acompaña a su pueblo y no lo deja ni lo abandona.",
  "La soledad duele, pero no es el final: Dios está en tu silencio. Hoy llama a alguien que no sabe de ti.",
  "No estás solo esta noche",
  "Señor, hoy me sentí solo. Hazte presente en el silencio de mi casa y envíame personas buenas. Gracias porque tú nunca me dejas. Amén."
 ],
 [
  "Comienza la Novena de Aguinaldos",
  "Isaías 9,5",
  "Un niño nos ha nacido, un hijo se nos ha dado.",
  "Hoy empieza la Novena. Reúne a tu familia, un villancico y un rato de oración valen más que muchos regalos.",
  "Primera noche de Novena",
  "Niño Dios, comenzamos la Novena para esperarte. Reúne a mi familia en la oración y la alegría. Ven, Señor Jesús, y quédate con nosotros. Amén."
 ],
 [
  "Les anuncio una gran alegría",
  "Lucas 2,10",
  "El ángel anuncia a los pastores una gran alegría para todo el pueblo.",
  "La alegría de Navidad es un anuncio: Dios está cerca. Contágiala con una visita o un mensaje.",
  "Segunda noche de Novena",
  "Señor, gracias por la alegría que se acerca. Quita mi tristeza y llena mi casa de cantos, perdón y cariño. Ven pronto, Niño Dios. Amén."
 ],
 [
  "Dichosa la que creyó",
  "Lucas 1,45",
  "Isabel dice a María: dichosa tú que creíste que se cumpliría lo que el Señor te dijo.",
  "María esperó confiando. Hoy puedes esperar así: sin ansiedad y con la certeza de que Dios cumple.",
  "Tercera noche de Novena",
  "Señor, bendice a las madres que esperan y a todos los que aguardan algo. Enséñanos a esperar sin desesperarnos. Amén."
 ],
 [
  "José hizo lo que el ángel le mandó",
  "Mateo 1,24",
  "José despertó y obedeció lo que el ángel del Señor le había mandado.",
  "José no dijo palabras; actuó con fidelidad. Honra hoy a los padres que cuidan en silencio.",
  "Cuarta noche de Novena",
  "Señor, gracias por José, el hombre justo. Bendice a los padres de familia y enséñanos a cumplir con sencillez nuestro deber. Amén."
 ],
 [
  "No había lugar en la posada",
  "Lucas 2,7",
  "María lo acostó en un pesebre, porque no había lugar para ellos en la posada.",
  "Haz un lugar: en tu mesa, en tu casa, en tu corazón, para quien hoy no lo tiene.",
  "Quinta noche de Novena",
  "Niño Dios, que mi corazón y mi casa tengan sitio para todos. Cuida a quienes no tienen techo y que nadie pase frío esta Navidad. Amén."
 ],
 [
  "Lo encontrarán en un pesebre",
  "Lucas 2,12",
  "La señal para los pastores: encontrarán al niño envuelto en pañales, acostado en un pesebre.",
  "Dios eligió lo pequeño para llegar a nosotros. Busca hoy su presencia en lo sencillo.",
  "Sexta noche de Novena",
  "Niño Dios, naciste en la pobreza. Enséñame a amar lo sencillo y a no dejarme llevar por la prisa de comprar. Amén."
 ],
 [
  "Los pastores fueron sin demora",
  "Lucas 2,8-9",
  "Los pastores cuidaban su rebaño de noche cuando se les apareció un ángel.",
  "Los primeros en saber fueron los sencillos. Dios sigue hablando a quien escucha con humildad.",
  "Séptima noche de Novena",
  "Señor, bendice a los que trabajan de noche y al campo. Hazme sencillo para escucharte y para anunciar tu alegría. Amén."
 ],
 [
  "Vimos su estrella",
  "Mateo 2,9-10",
  "Los Magos vieron la estrella y se llenaron de una inmensa alegría.",
  "Dios te muestra señales: una persona, una palabra, un impulso bueno. Síguelas.",
  "Octava noche de Novena",
  "Señor, cuando me sienta perdido, sé tú la estrella que me guíe. Acompáñame hasta Jesús y que ofrezca lo mejor de mí. Amén."
 ],
 [
  "Hoy les ha nacido un Salvador",
  "Lucas 2,11",
  "El ángel anuncia: hoy les ha nacido un Salvador, el Mesías, el Señor.",
  "Esta noche Dios nace. Abre la puerta de tu casa y de tu corazón.",
  "Nochebuena",
  "Niño Dios, bendice esta mesa, a los presentes y a los ausentes. Consuela a quienes pasan solos esta noche y trae tu paz a cada hogar de Colombia. Feliz Nochebuena. Amén."
 ],
 [
  "El Verbo se hizo carne",
  "Juan 1,14",
  "El Verbo se hizo carne y habitó entre nosotros.",
  "Dios no se quedó lejos; vive en tu casa, en tu trabajo y en tu familia. Hoy celébralo con un abrazo.",
  "Gracias, Niño Dios",
  "Señor Jesús, gracias porque naciste por nosotros. Quédate en mi hogar y en mi corazón. Que la paz de este día llegue a todos. ¡Feliz Navidad! Amén."
 ],
 [
  "Si el Señor no construye la casa",
  "Salmo 127,1",
  "Si el Señor no construye la casa, en vano trabajan los que la construyen.",
  "Una casa se sostiene en Dios y en el cariño de quienes la habitan. Pon a Dios en el centro de tu mesa.",
  "Bendice mi hogar",
  "Señor, bendice esta casa y a todos los que vivimos en ella. Que haya perdón, alegría y oración. Cubre nuestro sueño con tu protección. Amén."
 ],
 [
  "Jesús crecía en sabiduría",
  "Lucas 2,51-52",
  "Jesús volvió con José y María a Nazaret y crecía en sabiduría, estatura y gracia.",
  "La santidad se aprende en casa, en lo diario. Hoy haz de tu hogar un pequeño Nazaret.",
  "Familia bendecida",
  "Señor, gracias por la Sagrada Familia. Cuida a mi familia, une a los que están distantes y haz de nuestra casa un lugar de amor. Amén."
 ],
 [
  "Dejen que los niños vengan a mí",
  "Marcos 10,14",
  "Jesús dice: dejen que los niños vengan a mí, porque de ellos es el Reino de Dios.",
  "Defender a un niño es defender a Jesús. Hoy pregunta cómo puedes proteger a los más pequeños.",
  "Cuida a los niños",
  "Señor, protege esta noche a todos los niños: a los que tienen hambre, miedo o están solos. Despierta nuestro corazón para defenderlos. Amén."
 ],
 [
  "Hágase en mí según tu palabra",
  "Lucas 1,38",
  "María responde: aquí está la esclava del Señor, que se cumpla en mí tu palabra.",
  "Decir sí a Dios es confiar sin entenderlo todo. Un pequeño sí hoy puede cambiar tu historia.",
  "Que se haga tu voluntad",
  "Señor, aquí estoy. Que se cumpla en mí tu voluntad, con confianza y sin miedo. Gracias por lo vivido en este día. Amén."
 ],
 [
  "En paz me acuesto y duermo",
  "Salmo 4,9",
  "En paz me acuesto y enseguida me duermo, porque tú, Señor, me haces vivir tranquilo.",
  "Terminar el día en paz es un acto de fe. Suelta lo pendiente y deja que Dios vele por ti.",
  "En paz me acuesto",
  "Señor, al terminar el año te entrego mis cansancios y mis pendientes. Cuídame esta noche. Que mi sueño sea tranquilo y mañana despierte contigo. Amén."
 ],
 [
  "No olvides sus beneficios",
  "Salmo 103,2",
  "Bendice, alma mía, al Señor y no olvides ninguno de sus beneficios.",
  "Antes de empezar el año nuevo, haz memoria: ¿qué regalos de Dios recibiste este año?",
  "Gracias por este año",
  "Señor, termina el año y te doy gracias por todo: lo alegre y lo que dolió. Perdona mis faltas y recibe mis proyectos del año que empieza. Bendice a mi familia, a Tunja y a Colombia. Amén."
 ]
];
  return { ORACIONES: ORACIONES, MOODS: MOODS, NUEVAS: NUEVAS, PLAN: PLAN, MARIAS_DIA: MARIAS_DIA, MARIAS: MARIAS, FIJAS: FIJAS, MESES: MESES, DIAS: DIAS, dayNumber: dayNumber, TN: TN };
})();
