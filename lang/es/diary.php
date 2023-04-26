<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for diary plugin.
 *
 * @package   mod_diary
 * @copyright 2019 AL Rachels (drachels@drachels.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['accessdenied'] = 'Acceso denegado';
$string['additionallinks'] = 'Enlaces adicionales para esta actividad y curso:';
$string['addtofeedback'] = 'Agregar a retroalimentación';
$string['alias'] = 'Palabra clave';
$string['aliases_help'] = 'Cada entrada del diario puede tener una lista asociada de palabras claves.(ó alias).

Ingrese cada palabra clave en una nueva línea (no separada por comas).';
$string['aliases'] = 'Palabra(s) claves(s)';
$string['alwaysopen'] = 'Siempre Abierto';
$string['alwaysshowdescription_help'] = 'Si esta deshabilitado, la descripción del diario arriba sólo será visible para los estudiantes en la fecha de "tiempo abierto".';
$string['alwaysshowdescription'] = 'Siempre mostrar descripción';
$string['and'] = ' and ';
$string['attachment_help'] = 'Puede opcionalmente adjuntar uno o mas archivos a una entrada diaria.';
$string['attachment'] = 'Archivo';
$string['autorating_descr'] = 'Si está habilitado, la calificación para una entrada será automaticamente calculada basado en la configuración Min/Máx de conteo.';
$string['autorating_help'] = 'Esta configuración junto con el conteo Min/Max define por defecto la autorización en todos los diarios.';
$string['autorating_title'] = 'Auto-calificación habilitada';
$string['autorating'] = 'Auto-calificación';
$string['autoratingbelowmaxitemdetails'] = 'La Auto-calificación requiere {$a->one} ó más {$a->two} con un posible {$a->three}% castigo por cada faltante.<br> Tu tienes {$a->four}. Necesitas sacar {$a->five}. La posible penalización es {$a->six} puntos.';
$string['autoratingitempenaltymath'] = 'La matemática de la penalización de calificación automática de items es (max({$a->one} - {$a->two}, 0)) * {$a->three} =  {$a->four}.<br> Nota: max previene números negativos causados ​​por tener más de lo requerido.';
$string['autoratingitempercentset'] = 'La configuración del porcentaje de la  auto-calificación : {$a}%';
$string['autoratingovermaxitemdetails'] = 'El límite de la auto-calificación es un maximo de {$a->one} {$a->two} con un posible {$a->three}% penalización por cada extra.<br>Tu tienes {$a->four}, el cual es {$a->five} demasiado. La posible penalización es {$a->six} puntos.';
$string['availabilityhdr'] = 'Disponibilidad';
$string['avgsylperword'] = 'Promedio de sílabas por palabra {$a}';
$string['avgwordlenchar'] = 'Promedio de longitud de palabra {$a} caracteres';
$string['avgwordpara'] = 'Promedio de palabras por párrafo {$a}';
$string['blankentry'] = 'Entrada en blanco';
$string['calendarend'] = '{$a} cierra';
$string['calendarstart'] = '{$a} abre';
$string['cancel'] = 'Cancelar transferencia';
$string['chars'] = 'Caracteres:';
$string['charspersentence'] = 'Caracteres por sentencia';
$string['clearfeedback'] = 'Limpiar retroalimentación';
$string['commonerrorpercentset'] = 'Configuración del porcentaje de error común {$a}%';
$string['commonerrors_help'] = 'Los errores comunes son definidos en el "Glosario de errores" associados con esta pregunta.';
$string['commonerrors'] = 'Errores comunes';
$string['configdateformat'] = 'Esto define como las fechas son mostradas en los reportes del diario. El valor por defecto, "M d, Y G:i" es Mes, día, año y el formato de 24 horas. Refiérase a la fehca en el manual de PHP para más ejemplos y fechas constantes predefinidas.';
$string['createnewprompt'] = 'Crear nuevo aviso';
$string['created'] = 'Creado hace {$a->one} días y {$a->two} horas.';
$string['crontask'] = 'Procesamiento de fondo para el módulo de diario';
$string['csvexport'] = 'Exportar a .csv';
$string['currententry'] = 'Entradas del diario actuales:';
$string['currpotrating'] = 'Tu calificacion potencial actual es: {$a->one} puntos, ó {$a->two}%.';
$string['datechanged'] = 'Fecha cambiada';
$string['dateformat'] = 'Formato de fecha por defecto';
$string['datestart'] = 'Fijar fecha para comenzar usando el ID del aviso {$a}:';
$string['datestop'] = 'fijar fecha para dejar de usar el ID del aviso {$a}:';
$string['daysavailable_help'] = 'Si se usa el formato semanal, puede fijar cuántos dias el diario está abierto para usarse.';
$string['daysavailable'] = 'Días disponible';
$string['deadline'] = 'Días Abierto';
$string['delete'] = 'Borrar';
$string['deleteallratings'] = 'Borrar todas las calificaciónes';
$string['deletenotenrolled'] = 'Borrar las entradas de los usuarios no matriculados';


$string['deleteexconfirm'] = 'Confirme que está apunto de eliminar el ID del aviso de escritura';
$string['details'] = 'Detalles: ';
$string['detectcommonerror'] = 'Detectado al menos {$a->one}, {$a->two} Son: {$a->three}
<br>Si está permitido, deberia arreglar y re-enviar.';
$string['diary:addentries'] = 'Agregar entradas al diario';
$string['diary:addinstance'] = 'Agregar instancias al diario';
$string['diary:manageentries'] = 'Administrar entradas del diario';
$string['diary:rate'] = 'Calificar entradas del diario';
$string['diaryclosetime_help'] = 'Si está habilitado puede fijar una fecha para el cierre del diario y no pueda ser abierto para usarse.';
$string['diaryclosetime'] = 'Tiempo de cierre';
$string['diarydescription'] = 'Descripción del diario';
$string['diaryentrydate'] = 'fijar fecha para esta entrada';
$string['diaryid'] = 'Id de diario a transferir';
$string['diarymail'] = 'Felicitaciones {$a->user},
{$a->teacher} ha publicado una retroalimentación en la entrada de tu diario para \'{$a->diary}\'.

Ud la puede ver agregada a tu entrada del diario:

    {$a->url}';
$string['diarymailhtml'] = 'Felicitaciones {$a->user},<br>
{$a->teacher} Ha publicado una retroalimentacion en la entrada de tu diario para \'<i>{$a->diary}</i>\'.<br /><br />
La puedes ver agregada a tu <a href="{$a->url}">entrada del diario</a>.';
$string['diarymailhtmluser'] = 'Ha publicado una entrada del diario para \'<i>{$a->diary}</i>\'<br /><br />
Puede ver la <a href="{$a->url}">entrada del diario aquí</a>.<br /><br />Nota: Puede que sea necesario dar retroalimentación o actualizar el estatus de la entrada para la actvidad a ser completada.';

$string['diarymailuser'] = 'ha publicado una entrada del diario para \'{$a->diary}\'

Puede ver la entrada aquí:

    {$a->url}

Nota:Nota: Puede que sea necesario dar retroalimentación o actualizar el estatus de la entrada para la actvidad a ser completada.';
$string['diaryname'] = 'Nombre del Diario';
$string['diaryopentime_help'] = 'Si está habilitado, puede fijar una fecha para abrir el diario y usarse.';
$string['diaryopentime'] = 'Tiempo de apertura';
$string['editall_help'] = 'Cuando esta habilitado, los usuarios pueden editar cualquier entrada.';
$string['editall'] = 'Editar todas las entradas';
$string['editdates_help'] = 'Cuando está hablilitado, los usuarios pueden editar la fehca de cualquier entrada.';
$string['editdates'] = 'Editar fechas de la entrada';
$string['editingended'] = 'Ha terminando el período de edición';
$string['editingends'] = 'Finaliza período de edición';
$string['editthisentry'] = 'Editar esta entrada';
$string['edittopoflist'] = 'Editar el tope de esta lista';
$string['eeditlabel'] = 'Editar';
$string['emailpreference'] = 'Alternar correos';
$string['emailnow'] = 'Correo electrónico ahora';
$string['emaillater'] = 'Correo electrónico luego';
$string['enableautorating_help'] = 'Habilita o deshabilita la calificación automática';
$string['enableautorating'] = 'Habilita la calificación automática';
$string['enablestats_descr'] = 'Si está habilitado, las estadísticas para cada entrada serán mostradas.';
$string['enablestats_help'] = 'Hablita o deshabilita, la vista de estadística para cada entrada.';
$string['enablestats_title'] = 'Habilitar estadísticas';
$string['enablestats'] = 'Habilitar estadísticas';
$string['entries'] = 'Entradas';
$string['entry'] = 'Entrada';
$string['entrybgc_colour'] = '#93FC84';
$string['entrybgc_descr'] = 'Esto fija el color de fondo de una entrada/retroalimentación del diario.';
$string['entrybgc_help'] = 'Esto fija el color general de fondo de cada entrada y retroalimentación del diario.';
$string['entrybgc_title'] = 'Color de fondo de entrada/retroalimentación del diario';
$string['entrybgc'] = 'Color de fondo de entrada/retroalimentación del diario';
$string['entrycomment'] = 'Comentario de la entrada';
$string['entrysuccess'] = 'Tu entrada ha sido guardada! Puede ser necesario revisarla antes de que la actividad sea completada.';
$string['entrytextbgc_colour'] = '#EEFC84';
$string['entrytextbgc_descr'] = 'Esto fija el color de fondo del texto de una entrada del diario.';
$string['entrytextbgc_help'] = 'Esto fija el color de fondo del texto de una entrada del diario.';
$string['entrytextbgc_title'] = 'Color de fondo del texto del diario';
$string['entrytextbgc'] = 'Color de fondo del texto del diario';
$string['errorbehavior_help'] = 'Esta configuración refina el el comportamiento de coincidiencias para las entradas en el Glosario de errores comunes.';
$string['errorbehavior'] = 'Error de comportamiento de coincidencias'
$string['errorcmid_help'] = 'Escoga el Glosario que contiene una lista de los errores comunes. Cada vez que se encuentre uno de los errores en la respuesta del ensayo, la penalización específica sera deducida de las calificaciones del estudiante para esta entrada.';
$string['errorcmid'] = 'Glossary of errors';
$string['errorpercent_help'] = 'Seleccione el porcentaje total de calificación que debe ser deducido por cada  error that is found in the response.';
$string['errorpercent'] = 'Penalización por error';
$string['errp'] = ' Err %: ';
$string['eventdiarycreated'] = 'Diario creado';
$string['eventdiarydeleted'] = 'Diario borrado';
$string['eventdiaryviewed'] = 'Diario visto';
$string['eventdownloadentriess'] = 'Descargar entradas';
$string['evententriesviewed'] = 'Entradas del diario vistas';
$string['evententrycreated'] = 'Entradas del diario creadas';
$string['evententryupdated'] = 'Entradas del diario actualizadas';
$string['eventfeedbackupdated'] = 'Retroalimentación del diario actualizada';
$string['eventinvalidentryattempt'] = 'Intento de entrada del diario inválida';
$string['eventpromptcreated'] = 'Aviso creado';
$string['eventpromptedited'] = 'Aviso editado';
$string['eventpromptinuse'] = 'Eliminacion rápida impedida ';
$string['eventpromptremoved'] = 'Solicitud removida';
$string['eventpromptsviewed'] = 'Solicitudes vistas';
$string['eventxfrentries'] = 'Transferencia de Periódico a Diario';
$string['exportfilename'] = 'entriadas.csv';
$string['exportfilenamep1'] = 'Todo_elsitio';
$string['exportfilenamep2'] = '_Entradas_Diarias_Exportadas_En_';
$string['feedbackupdated'] = 'Retroalimentación actualizada para {$a} entradas';
$string['files'] = 'Archivos';
$string['firstentry'] = 'Pfimeras entradas del diario:';
$string['fkgrade_help'] = 'El nivel de grado de Flesch Kincaid indica la cantidad de años de educación que generalmente se requieren para comprender este texto. Trate de aspirar a un nivel de grado por debajo de 10.';
$string['fkgrade'] = 'Calificacion FK';
$string['fogindex_help'] = 'El índice de niebla de Gunning es una medida de legibilidad. Se calcula mediante la siguiente fórmula.

 ((palabras por oración) + (palabras largas por oración)) x 0.4

 Trate de aspirar a un nivel de grado por debajo de 10. Para obtener más información, consulte: <https://en.wikipedia.org/wiki/Gunning_fog_index>';
$string['fogindex'] = 'Indice de niebla';
$string['for'] = ' para el siio: ';
$string['format'] = 'Formato';
$string['freadingease_help'] = 'Facilidad de lectura de Flesch: las puntuaciones altas indican que su texto es más fácil de leer, mientras que las puntuaciones bajas indican que su texto es más difícil de leer. Intenta apuntar a una facilidad de lectura superior a 60.';
$string['freadingease'] = 'Facilidad de lectura de Flesch';
$string['generalerror'] = 'Ha ocurrido un error.';
$string['generalerrorinsert'] = 'No se pudo insertar una nueva entrada al diario.';
$string['generalerrorupdate'] = 'No se pudo actualizar tu diario.';
$string['gradeingradebook'] = 'Actual calificación en el libro de calificaciones';
$string['highestgradeentry'] = 'Las entradas con más alta calificación:';
$string['idlable'] = ' (ID: {$a})';
$string['incorrectcourseid'] = 'La ID del curso es incorrecta';
$string['incorrectmodule'] = 'El ID del módulo del curso es incorrecto';
$string['invalidaccess'] = 'Acceso inválido';
$string['invalidaccessexp'] = 'No tiene permiso para ver esta pagina que intentas acceder! el intento fue resitrado!';
$string['invalidtimechange'] = 'Un intento válido para cambiar esta entrada, el tiempo de creación ha sido detectado. ';
$string['invalidtimechangenewtime'] = 'El tiempo cambidado fué: {$a->one}. ';
$string['invalidtimechangeoriginal'] = 'El tiempo original era: {$a->one}. ';
$string['invalidtimeresettime'] = 'El tiempo fue resetado al tiempo original de: {$a->one}.';
$string['journalid'] = 'ID de periódico a transferir';
$string['journalmissing'] = 'Actualemente, no hay ninguna actividad de Periódico en este curso.';
$string['journaltodiaryxfrdid'] = '<br>Esta es una lista de cada actividad del Diario en este curso.<br><b>    ID</b> | Curso | Nombre del Diario<br>';
$string['journaltodiaryxfrjid'] = 'Esta es una lista de cada actividad del Periódico en este curso.<br><b>    ID</b> | Curso | Nombre del Periódico<br>';
$string['journaltodiaryxfrp1'] = 'Esta es una funcion única del administrador para transferir entradas del Periódico a entradas del Diario. Entradas de multiples Periódicos pueden ser transferidos a un Diario sencillo ó a mu can be transferred to a single Diary ó a diarios múltiples separados. Esta es una nueva capacidad y aun está bajo desarrollo.<br><br>';
$string['journaltodiaryxfrp2'] = ' Si usa el checkbox de <b>Transferencia y envio de correo electrónico </b>, cualquier entrada de periódico trasnferido a una actividad del diario marcará la  nueva entrada como necesidad de enviar un correo electrónico a los usuarios para que ellos sepan que la entrada ha sido transferida hacia una actividad del diario.<br><br>';
$string['journaltodiaryxfrp3'] = 'Si tu usas el botón <b>Transferir sin correo electrónico</b>, un correo electrónico No será enviado a cada usuario aunque el el proceso agrega retroalimentacion automáticamente en la nueva entrada del Diario incluso si el proceso agrega automaticamente retroalimentación en la nueva entrada del Diario, incluso si la entrada Original del Periódico no tuvo retroalimentación.<br><br>';
$string['journaltodiaryxfrp4'] = 'El nombre de este curso en el que estás trabajando es: <b> {$a->one}</b>, con un ID de curso de: <b> {$a->two}</b><br><br>';
$string['journaltodiaryxfrp5'] = 'Si eliges incluir retroalimentacion con respecto a la transferencia y la entrada del periódico aún no tiene alguna retroalimentación, serás agregado automáticamente como el profesor de la entrada para prevenir un error.<br><br>';
$string['journaltodiaryxfrtitle'] = 'Periódico a Diario xfr';
$string['lastnameasc'] = 'Apellido ascendiente:';
$string['lastnamedesc'] = 'Apellido descendiente:';
$string['latestmodifiedentry'] = 'Las entradas modificadas más recientemente:';
$string['lexicaldensity_help'] = 'La densidad del léxico es un porcentaje calculado usando la siguiente fórmula.

 100 x (número de palabras únicas) / (total numero de palabaras)

Además, un ensayo en el cual muchas palabras están repetidas tiene una baja densidad léxica, mientras un ensayo con muchas palabras únicas tiene una densidad léxica alta.';
$string['lexicaldensity'] = 'Densidad Léxica';
$string['longwords_help'] = 'Las palabras largas son las que tienen tres o más sílabas. Note que el algortimo para determinar el número de sílabas se basa solamente en resultadps aproximados.';
$string['longwords'] = 'Palbras largas únicas';
$string['longwordspersentence'] = 'Palabras largas por oración';
$string['lowestgradeentry'] = 'Entradas con calificación mas baja:';
$string['mailed'] = 'Correo enviado ';
$string['mailsubject'] = 'Retroalimentacion del Diario';
$string['max'] = ' max';
$string['maxc'] = ' Max: ';
$string['maxchar_help'] = 'Si un número mayor a cero es ingresado, el usuario debe usar menos caracteres que el número máximo listado, ó recibir una penalización por cada caracter adicional.';
$string['maxchar'] = 'Cantidad máxima de caracteres';
$string['maxcharacterlimit_desc'] = 'Nota: Esta entrada puede usar un <strong>máximo de {$a} caracteres.</strong>';
$string['maxcharacterlimit_help'] = 'Si ingresa un número, el usuario debe usar menos caracteres que el numero máximo listado.';
$string['maxcharacterlimit'] = 'Cantidad máxima de caracteres';
$string['maxparagraph_help'] = 'Si un número mayor a cero es ingresado, el usuario debe usar menos párrafos que el número máximo listado, ó recibirá una penalización por cada uno de los párrafos adicionales.';
$string['maxparagraph'] = 'Cantidad Máxima de párrafos';
$string['maxparagraphlimit_desc'] = 'Nota: Esta en entrada puede usar un <strong>máximuo de {$a} párrafos.</strong>';
$string['maxparagraphlimit_help'] = 'Si un número es ingresado, el usuario debe usar menos párrafos que que el número máximo listado.';
$string['maxparagraphlimit'] = 'Cantidad máxima de párrafos';
$string['maxpossrating'] = 'La calificación máxima posible para esta entrada es {$a} puntos.';
$string['maxsentence_help'] = 'Si un número mayor a cero es ingresado, el usuario debe usar menos oraciones que el número máximo listado, o recibirá penalización por cada oración adicional.';
$string['maxsentence'] = 'Cantidad máxima de oraciones';
$string['maxsentencelimit_desc'] = 'Nota: Esta entrada puede usar una <strong>máxima de {$a} oraciones.</strong>';
$string['maxsentencelimit_help'] = 'Si un numero es ingresado, el usuario debe usar menos oraciones que el número máximo listado.';

$string['maxsentencelimit'] = 'Cantidad máxima de oraciones';
$string['maxword_help'] = 'Si un número mayor a cero es ingresado, el usuario debe usar menos palabras que el número maximo listado, ó será penalizado por cada palabra extra.';
$string['maxword'] = 'Cantidad máxima de palabras';
$string['maxwordlimit_desc'] = 'Nota: Esta entrada puede usar un <strong>máximo de {$a} palabras.</strong>';
$string['maxwordlimit_help'] = 'Si un numero es ingresado, el usuario debe usar menos palabras que  el número máximo listado.';
$string['maxwordlimit'] = 'Cantidad máxima de palabras';
$string['mediumwords_help'] = 'Las palabras medias son las palabras que tienen dos sílabas. Note que el algortimo para determinar el número de sílabas es basado unicamente en resultados aproximados.';
$string['mediumwords'] = 'Palabras medias únicas';
$string['min'] = ' min';
$string['minc'] = ' Min: ';
$string['minchar_help'] = 'Si un número mayor a cero es ingresado, el usuario debe usar más caracteres que el número máximo listado, ó recibirá una penalización por cada  . faltante caracteres.';
$string['minchar'] = 'Cantidad de caracteres mínimo';
$string['mincharacterlimit_desc'] = 'Nota: Esta entrada debe usar un <strong>mínimo of {$a} caracteres.</strong>';
$string['mincharacterlimit_help'] = 'Si un número es ingresado, el usuario debe usar más caracteres que el número máximo listado.';
$string['mincharacterlimit'] = 'Cantidad de caracteres mínimo';
$string['minmaxcharpercent_help'] = 'Seleccione el porcentaje total de calificación que debe ser deducido por cada  Min/Max Cantidad de caracteres error.';
$string['minmaxcharpercent'] = 'Penalización de caracter por error de conteo Min/Max ';
$string['minmaxhdr_help'] = 'Estas configuraciones definen el valor por defecto del mínimo y máximo caracter y del conteo de palabras en todos los diarios nuevos.';
$string['minmaxhdr'] = 'Conteo Min/Max ';
$string['minmaxparagraphpercent_help'] = 'Seleccione el porcentaje total de calificación que debe ser deducido por cada Min/Max  error de conteo de párrafo.';
$string['minmaxparagraphpercent'] = 'Penalización de párrafo por error de conteo Min/Max ';
$string['minmaxparapercent_help'] = 'Seleccione el porcentaje total de calificación que debe ser deducido por cada error de conteo Min/Max  de párrafo.';
$string['minmaxparapercent'] = 'Penalización de párrafo por error de conteo Min/Max ';
$string['minmaxpercent_help'] = 'Seleccione el porcentaje total de calificación que debe ser deducido por cada error de conteo Min/Max .';
$string['minmaxpercent'] = 'Penalización per error de conteo Min/Max ';
$string['minmaxsentencepercent_help'] = 'Seleccione el porcentaje total de calificación que debe ser deducido por cada error de conteo Min/Max  de oraciones.';
$string['minmaxsentencepercent'] = 'Penalizacion de oración por error de conteo Min/Max ';
$string['minmaxsentpercent_help'] = 'Seleccione el porcentaje total de calificación que debe ser deducido por cada error de conteo Min/Max  de oraciones.';
$string['minmaxsentpercent'] = 'Penalizacion de oración error de conteo Min/Max ';
$string['minmaxwordpercent_help'] = 'Seleccione el porcentaje total de calificación que debe ser deducido por cada error de conteo Min/Max  de palabras.';
$string['minmaxwordpercent'] = 'Word penalty per error de conteo Min/Max ';
$string['minparagraph_help'] = 'Si un número mayor a cero es ingresado, el usuario debe usar más párrafos que el número máximo listado, ó recibirá una penalización por cada párrafo faltante.';
$string['minparagraph'] = 'Cantidad de párrafos mínimo';

$string['minparagraphlimit_desc'] = 'Nota: Esta entrada debe usar un  <strong>mínimo of {$a} párrafos.</strong>';
$string['minparagraphlimit_help'] = 'Si un número es ingresado, el usuario debe usar más párrafos que el número máximo listado.';
$string['minparagraphlimit'] = 'Cantidad de párrafos mínimo';
$string['minsentence_help'] = 'Si un número mayor a cero es ingresado, el usuario debe usar más oraciones que el número máximo listado, ó recibirá una penalización por cada oración faltante.';
$string['minsentence'] = 'Cantidad de oraciones mínima';
$string['minsentencelimit_desc'] = 'Nota: Esta entrada debe usar un  <strong>mínimo of {$a} oraciones.</strong>';
$string['minsentencelimit_help'] = 'Si un número es ingresado, el usuario debe usar más oraciones que el número máximo listado.';
$string['minsentencelimit'] = 'Cantidad de oraciones mínima';
$string['minword_help'] = 'Si un número mayor a cero es ingresado, el usuario debe usar más palabras que el número máximo listado, ó recibirá una penalización por cada  . faltante palabras.';
$string['minword'] = 'Cantidad de palabras mínimo';
$string['minwordlimit_desc'] = 'Nota: Esta entrada debe usar un  <strong>mínimo of {$a} palabras.</strong>';
$string['minwordlimit_help'] = 'Si un número es ingresado, el usuario debe usar más palabras que el número máximo listado.';
$string['minwordlimit'] = 'Cantidad mínima de palabras';
$string['missing'] = 'Inexistente';
$string['modulename_help'] = 'El diario de esta actividad habilira a los profesores obtener retroalimentacion de los estudiantes
 en un período de tiempo.';
$string['modulename'] = 'Diario';
$string['modulenameplural'] = 'Diarios';
$string['needsgrading'] = ' Esta entrada no ha tenido una retroalimentación o calificada aún.';
$string['needsregrade'] = 'Esta entrada no ha cambiado desde que fué dada una retroalimentacion o calificación.';
$string['newdiaryentries'] = 'Entradas de nuevo diario';
$string['nextentry'] = 'Próxima entrada';
$string['nodeadline'] = 'Siempre Abierto';
$string['noentriesmanagers'] = 'No hay profesores';
$string['noentry'] = 'Sin esntrada';
$string['noratinggiven'] = 'Sin calificación';
$string['notextdetected'] = '<b>Texto no detectado!</b>';
$string['notopenuntil'] = 'Este diario no será abierto hasta';
$string['notstarted'] = 'No has comenzado este diario aún';
$string['numwordscln'] = '{$a->one} Palabras de texto limpiadas usando {$a->two} caracteres, SIN incluir {$a->three} espacios. ';
$string['numwordsnew'] = 'Nuevo Cálculo: {$a->one} texto aleatorio palabras de texto sin procesar usando {$a->two} caracteres, en {$a->three} oraciones, in {$a->four} párrafos. ';
$string['numwordsraw'] = '{$a->one} palabras de texto sin procesar usando  {$a->two} caracteres, incluyendo {$a->three} espacios. ';
$string['numwordsstd'] = '{$a->one} palabras estandardizadas usando {$a->two} caracteres, incluyendo {$a->three} espacios. ';
$string['outof'] = ' fde {$a} entradas.';
$string['overallrating'] = 'Calficacion general
';
$string['pagesize'] = 'Entradas por página';
$string['paragraphs'] = 'Párrafos:';
$string['percentofentryrating'] = '{$a}% de la calificación de la entrada.';
$string['phrasecasesensitiveno'] = 'La coincidencia no distingue entre mayúsculas y minúsculas.';
$string['phrasecasesensitiveyes'] = 'La coincidencia distingue entre mayúsculas y minúsculas.';
$string['phrasefullmatchno'] = 'Coincidencia de palabras completas o parciales.';
$string['phrasefullmatchyes'] = 'Coincidencia sólo de palabras completas.';
$string['phraseignorebreaksno'] = 'Reconocer saltos de línea.';
$string['phraseignorebreaksyes'] = 'Ignorar saltos de línea.';
$string['pluginadministration'] = 'Administración del módulo del diario ';
$string['pluginname'] = 'Diario';
$string['popoverhelp'] = 'click para información';
$string['potautoratingerrpen'] = 'Penalización potencial por error de autorización: {$a->one}% or {$a->two} puntos fuera.';
$string['potcommerrpen'] = 'Potencial penalizacón por error común: {$a->one} * {$a->two} = {$a->three}% ó {$a->four} puntos fuera.';
$string['present'] = 'Presente';
$string['previousentry'] = 'Entrada previa';
$string['privacy:metadata:diary_entries:diary'] = 'El ID de la actividad del diario en el cual la entrada fué publicada.';
$string['privacy:metadata:diary_entries:entrycomment'] = 'La retroalimentacón del profesor y posiblemente, retroalimentacion de calificación automática.';
$string['privacy:metadata:diary_entries:mailed'] = 'Este usuario ya ha recibido un correo?';
$string['privacy:metadata:diary_entries:promptid'] = 'El ID del aviso automático de escritura usado para auto-calificación y retroalimentación.';
$string['privacy:metadata:diary_entries:promptdatestart'] = 'La fecha que el aviso de escritura automática comenzó a ser usado.';
$string['privacy:metadata:diary_entries:promptdatestop'] = 'La fecha que el aviso de escritura automática dejó de ser usado.';
$string['privacy:metadata:diary_entries:prompttext'] = 'El texto del mensaje de escritura usado para auto-calificación y retroalimentación.';
$string['privacy:metadata:diary_entries:rating'] = 'La calificación númerica para esta entrada del diario. Puede ser determinada por escalas/formularios de notas avanzadas, etc., pero siempre sera convertido de vuelta a un número punto flotante.';
$string['privacy:metadata:diary_entries:teacher'] = 'El ID del usuario de la persona calificando la entrada.';
$string['privacy:metadata:diary_entries:text'] = 'El contenido de esta entrada.';
$string['privacy:metadata:diary_entries:timecreated'] = 'Tiempo en el que la entrada fue creada.';
$string['privacy:metadata:diary_entries:timemarked'] = 'Tiempo en el que la entrada fué calificada.';
$string['privacy:metadata:diary_entries:timemodified'] = 'El tiempo en el que la entrada fué modificada por última vez.';
$string['privacy:metadata:diary_entries:userid'] = 'ID del usuario.';
$string['privacy:metadata:diary_entries'] = 'Un registro de una entrada del diario.';
$string['prompt'] = 'Ingrese su mensaje de escritura';
$string['promptid'] = 'Prompt id';
$string['promptinfo'] = 'Hay {$a->past} mensajes pasados, {$a->current} actual mensaje, y {$a->future} mensajes futuros para esta actividad del diario.<br>';
$string['promptmaxc'] = 'max Caracter ';
$string['promptmaxp'] = 'max Párrafo ';
$string['promptmaxs'] = 'max Enviado ';
$string['promptmaxw'] = 'max Palabra';
$string['promptminc'] = 'min Caracter';
$string['promptminp'] = 'min Párrafo';
$string['promptmins'] = 'min Enviado';
$string['promptminw'] = 'min Palabra';
$string['promptminmaxcp'] = 'Caracter %';
$string['promptminmaxpp'] = 'Para %';
$string['promptminmaxsp'] = 'Enviado %';
$string['promptminmaxwp'] = 'Palabra %';

$string['promptremovefailure'] = 'Este mensaje, ID {$a}, está en uso y no puede ser removido.';
$string['promptremovesuccess'] = 'Has removido exitosamente el mensaje, ID {$a}.';
$string['promptstart'] = 'Inicio rápido';
$string['promptstitle'] = 'Indicaciones para escribir al diario';
$string['promptstop'] = 'parada inmediata';
$string['promptsviewtitle'] = 'Ver solicitudes de escritura';
$string['prompttext'] = 'Texto de solicitud';
$string['promptzerocount'] = '<td>Actualmente hay {$a} solicitudes para esta actividad del Diario. </td>';
$string['rate'] = 'Calificación';
$string['rating'] = 'Calificación para esta entrada';
$string['reload'] = 'Recargar y mostrar desde actual hasta la entrada más vieja del diario';
$string['removealldiarytags'] = 'Remover todas las etiquetas del Diario';

$string['removeentries'] = 'Remover todas las entradas';
$string['removemessages'] = 'Remover todas las entradas del Diario';
$string['reportsingle'] = 'Obtener toddas las entradas del diario de este usuario.';
$string['reportsingleallentries'] = 'Todas las entradas del diario de este usuario.';
$string['returnto'] = 'Volver a {$a}';
$string['returntoreport'] = 'Regresar a la página de reportes para - {$a}';
$string['saveallfeedback'] = 'Guardar todas mis retroalimentaciones';
$string['savesettings'] = 'Guardad configuraciones';
$string['search:entry'] = 'Diario - entradas';
$string['search:entrycomment'] = 'Diario - comentario de entrada';
$string['search:activity'] = 'Diario - información de actividad';
$string['search'] = 'Buscar';
$string['selectentry'] = 'Selecciona una entrada para maracado';
$string['sentences'] = 'oraciones:';
$string['sentencesperparagraph'] = 'oraciones por párrafo';
$string['shortwords_help'] = 'Las palabras cortas son palabras que tienen una sílaba. Note que el algoritmo que determina el número de sílabas se basa sólo en resultados aproximados.';
$string['shortwords'] = 'Palabras cortas únicas';
$string['shownone'] = 'Mostrar ninguno';
$string['showoverview'] = 'Mostrar vista general en mi moodle';
$string['showrecentactivity'] = 'Mostrar actividad reciente';
$string['showstudentsonly'] = 'Mostrar sólo estudiantes';
$string['showteacherandstudents'] = 'Mostrar profesores y estudiantes';
$string['showteachersonly'] = 'Mostrar sólo profesores';
$string['showtextstats_help'] = ' Si esta opción está habilitada, las estadísticas acerca del texto serán mostradas.';
$string['showtextstats'] = 'Mostrar estadísticas de texto?';
$string['showtostudentsonly'] = 'Si, mostrar solo a estudiantes';
$string['showtoteachersandstudents'] = 'Si, mostrar a los profesores y estudiantes';
$string['showtoteachersonly'] = 'Si, mostrar solo a los profesores';
$string['sortcurrententry'] = 'Desde entrada actual del diario a la primera entrada.';
$string['sortfirstentry'] = 'Desde la primera entrada del diario a la última.';
$string['sorthighestentry'] = 'Desde la entrada con calificación más alta hasta la entrada con calificación más baja.';
$string['sortlastentry'] = 'Desde la última entrada del diario modificada has la más vieja modificada.';
$string['sortlowestentry'] = 'Desde la entrada calificada más baja hasta la entrada calificada mas alta.';
$string['sortoptions'] = ' Opciones ordenamiento: ';
$string['sortorder'] = 'El orden de clasificación es: ';
$string['startnewentry'] = 'Comenzar nueva entrada';
$string['startoredit'] = 'Comenzar nueva entrada o editar';
$string['statshdr_help'] = 'Estas configuraciones definen el valor por defecto para las estadísticas en todos los nuevos diarios.';
$string['statshdr'] = 'Texto de Estadísticas';
$string['statshide'] = 'Ocultar estadístics';
$string['statsshow'] = 'Mostrar estadísticas';
$string['studentemail'] = 'Enviar notificaciones por correo electrónico a estudiantes';
$string['studentemail_help'] = 'Habilite ó deshabilite la capacidad  para  enviar notificaciones por correo electrónico a los estudiantes.';
$string['tablecolumncharacters'] = 'caracteres';
$string['tablecolumnedit'] = 'Editar&nbsp;&nbsp;&nbsp;&nbsp;';
$string['tablecolumnparagraphs'] = 'Párrafos';
$string['tablecolumnprompts'] = 'Avisos';
$string['tablecolumnsentences'] = 'oraciones';
$string['tablecolumnstart'] = 'Comenzar';
$string['tablecolumnstatus'] = 'Estatus';
$string['tablecolumnstop'] = 'Parar';
$string['tablecolumnwords'] = 'Palabras&nbsp;&nbsp;&nbsp;&nbsp;';
$string['tagarea_diary_entries'] = 'Entradas del diario';
$string['tcount'] = 'Actualmente, esta actividad del diario tiene un total de {$a} avisos de escritura que pertencen a ella.<br>';
$string['teacher'] = 'Profesor';
$string['teacheremail'] = 'Envío de notificaciones a los profesores';
$string['teacheremail_help'] = 'Habilite ó deshabilite la capacidad para enviar notifiaciones por correo electrónico a los profesores.';
$string['text_editor'] = 'PTexo de Aviso';
$string['text'] = 'Ingresa tu avsido de escritura';
$string['textstatitems_help'] = 'Seleccione aquí algún artículo que tu deseas que aparezca en las estadísticas que son mostradas en la vista de página, página de reporte y  página de reporte sencillo .';
$string['textstatitems'] = 'Artículos estadísticos';
$string['timecreated'] = 'Tiempo creado';
$string['timemarked'] = 'Tiempo marcado';
$string['timemodified'] = 'Tiempo modificado';
$string['toolbar'] = 'Barra de herramientas:';
$string['totalsyllables'] = 'Total de sílabas {$a}';
$string['transfer'] = 'Transferencia de entradas';
$string['transferwemail'] = 'Transferir y enviar correo electrónico. <b>Default: No envíe el correo electrónico</b>';
$string['transferwfb'] = 'Transferir e incluir retroalimentación acerca de la transferencia. <b>Default: No incluye retroalimentación</b>';
$string['transferwfbmsg'] = '<br> Esta entrada fue transferida desde el  Periódico llamado:  {$a}';
$string['transferwoe'] = 'Transferencia sin correo electrónico';
$string['uniquewords'] = 'Palabras únicas';
$string['userid'] = 'ID de usuario';
$string['usertoolbar'] = 'Barra de herramientas del usuario:';
$string['viewalldiaries'] = 'Ver todos los diarios del curso';
$string['viewallentries'] = 'Ver {$a} entradas del diario';
$string['viewentries'] = 'Ver entradas';
$string['warning'] = '<b>ADVERTENCIA - Tienes {$a} avisos actuales, lo que es un error. No puedes tener múltiples, solapando fechas actuales!  Esto debe ser solucionado!</b><br>';
$string['words'] = 'Palabras:';
$string['wordspersentence'] = 'Palabras por oración';
$string['writingpromptlable'] = 'Indicación de escritura actual: {$a->counter} (ID: {$a->entryid}) que empezó en {$a->starton} y terminará en {$a->endon}.<br>{$a->datatext}';
$string['writingpromptlable2'] = 'Mensaje de escritura: ';
$string['writingpromptlable3'] = 'Editor de mensaje de escritura';
$string['writingpromptnotused'] = 'Las configuraciones normales del diario usadas para esta configuración de porcentaje de auto calificación de la entrada .';
$string['writingpromptused'] = 'ID de Mensaje de escritura : {$a} fué usada por esta configuración de porcentaje de auto calificación de la entrada .';
$string['xfrresults'] = 'Había {$a->one} entrada procesada, y {$a->two} de ellas transferida.';
