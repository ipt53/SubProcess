<?php
  $start = microtime(true);
  if (file_exists('log.txt')) unlink('log.txt'); 
  set_time_limit(0);
  
  
/**
*	Класс позволяет работать с подпроцессами в параллельном режиме, асинхронно
*	Хорошо работает на больших данных: чем больше число заявок, тем меньше среднее время
*	Среднее время обработки 10 000 "заявок" 3 минуты 25 секунд
*   Одновременно выполняется 100 подпроцессов, как только заканчивается один из них запускается новый
*/
class SubProcess 
{
    /**
     * @var resurse выполняемый подпроцесс
     */
	public $process;
	
	/**
     * @var int количество "заявок", которые необходимо выполнить
     */
    public static $maxRequest = 10000;
	
    /**
     * @var int максимально количество одновременно запускаемых процессов
     */
	public static $pack = 100; 
	
	/**
     * @var array Файловые дескрипторы для каждого подпроцесса.
     */
	public static $fdesc = array(
		0 => array('pipe', 'r'), // stdin
		1 => array('pipe', 'w'), // stdout
		2 => array('file', 'error.txt', 'a') // stderr
	);
	
	/**
     * @var string Команда для выполнения оболочкой (php) с параметрами
     */
	public $cmd;
	
	/**
     * @var array Будет установлен в индексированный массив файловых указателей,
	 * соответствующий концу любых созданных дочерних каналов.
     */
	public $pipes = array();
	
	/**
     * Конструктор
     */
	function __construct($cmd)
	{
		$this -> cmd = $cmd;
	}
	
	/**
     * Узнаём работает ли ещё подпроцесс
     */
	public function IsRunningProcess()
	{
		$meta = proc_get_status($proc);
		return $meta['running'];
	}
	
	/**
     * Закончил ли подпроцесс выполнятся
     */
	public function IsStopProcess()
	{
		$meta = proc_get_status($this -> process);
		return !$meta['running'];
	}
	
	/**
     * Запускает подпроцесс и снимает блок с выходного канала stdout
     */
	public function run()
	{
		$this -> process = proc_open($this -> cmd, self::$fdesc, $helpPipes); 
		// Делаем выходной канал подпроцесса незаблокированным.
		stream_set_blocking($helpPipes[1], false);
		$this -> pipes = $helpPipes;
	}
	
	/**
     * Закрывает подпроцесс и выходной канал
     */
	public function close()
	{
		fclose($this -> pipes[1]);
		proc_close($this -> process);
	}
	
	/**
     * Определяет тип "заявки" и создаёт подпроцесс (но не запускает)
     */
	public function NewRequest($i)
	{
		$typeRequest = rand(1,4);
		switch($typeRequest){
			case 1: $proc = new SubProcess('php casco.php casco' . $i); break;
			case 2: $proc = new SubProcess('php osago.php osago' . $i); break;
			case 3: $proc = new SubProcess('php credit.php credit' . $i); break;
			case 4: $proc = new SubProcess('php finance.php finance' . $i); break;
		}
		return $proc;
	}
}
?>

<?php
	
	// активные процессы
	$activProc = array();
	
	// сколько "новых заявок" поступило
	$next = SubProcess::$pack;
	
	// Открываем максимальное количество подпроцессов
	foreach (range(1, $next) as $i) {
		$activProc[$i] = SubProcess::NewRequest($i);
		$activProc[$i] -> run();
	}
	
	/**
     * То чувство когда у нас есть свободные 2 секунды (по условиям задачи),
	 * все подпроцессы выполняются и мы млжем сдедать что-то полезное, например, 
	 * проверить не закончился ли какой-нибудь процесс, закрыть его и начать новый,
	 * и вывести на экран результаты работы подпроцессов
     */
	$max = SubProcess::$maxRequest;

	do{
		usleep(10 * 1000); // 100ms
		$run = 0;
		// Как только заканчивается один процесс, мы сразу же запускаем следующий, проверяем каждые 100ms.
		foreach ($activProc as $key => $procOut) {
			
			// Читаем из потока весь доступный вывод (непрочитанный вывод буферизован).
			$string = fread($procOut -> pipes[1], 1024);
			if ($string) { printf($string); }
			
			if($procOut -> IsStopProcess()) {
				// Тут надо закрыть процесс и уничтожить элемент массива.
				$procOut -> close();
				unset($activProc[$key]);
			
				$next++; // надо прекратить запускать подпроцессы когда их количество уже = $maxRequest.
				
				if($next < ($max +1)){
					$activProc[$next] = SubProcess::NewRequest($next);
					$activProc[$next] -> run();
				}
			}else{
				$run++; // если есть работающие процессы мы не должны выходить из цикла.
			}
		}
		
	}while ($run);

	// Закрываем все оставшиеся подпроцессы и каналы вывода.
	foreach ($activProc as $key => $value){
			$activProc[$key] -> close();
			unset($activProc[$key]);
	}




$finish = microtime(true);
$sec = $finish - $start; $min = floor($sec/60); $sec = $sec - ( 60* $min);
echo '<br>Время выолнения: '.$min.' минут '.$sec.' секунд<br>';
?>