<?php

/* accepted values for bounds */
define('VALID_BOUNDS', ['[', ']']);

/*  
la classe Interval dovrà rappresentare un intervallo limitato che prevede due estremi (min e max) ed i relativi limiti che possono essere inclusi o esclusi 
es.  
[1,7]  -> da 1 a 7 inclusi
]2,8]  -> da 2 (escluso) a 8 (incluso)
]4,17[ -> da 4 a 17 esclusi 
su tale intervallo vi dev'essere la possibilità di eseguire delle operazioni matematiche come l'unione, l'intersezione e la differenza dando in input un ulteriore intervallo di confronto.

tali operazioni dovranno essere possibili su più tipologie di intervalli : Integer, Float, DateTime.
--- ANALYSES ---
1. Si sceglie come notazione dei limiti -> [], ogni altra sintassi per indicare i limiti dell'intervallo verrà riconosciuta come errore e la loro presenza è strettamente obbligatoria al fine di una corretta notazione
2. Controllo dei valori inseriti -> i valori separati dalla ',' potranno essere Integer, Float e DateTime; altre tipologie di dato saranno riconosciute come errore nell'inserimento dell'intervallo
3. Controllo ordini di grandezza -> si prevede la possibilità che gli estremi dati in input siano invertiti ma non la notazione dei limiti
4. Presenza di eventuali spazi vuoti e caratteri "illegali" all'interno
5. Si prevede la possibilità che l'input sia non corretto oppure vuoto -> restituendo un errore

--- PROPERTIES ---
$min -> primo valore dell'intervallo
$min_bound -> incluso o escluso (true o false) 
$max -> ultimo valore dell'intervallo
$max_bound -> incluso o escluso (true o false) 
*/
class Interval
{
  /**
   * @var mixed
   * @access private
   * min value for interval
   */
  private $min;

  /**
   * @var boolean
   * @access private
   * min included in interval = true
   */
  private $min_bound;

  /**
   * @var mixed
   * @access private
   * max value for interval
   */
  private $max;

  /**
   * @var boolean
   * @access private
   * max included in interval = true
   */
  private $max_bound;

  /**
   * @param mixed $input
   * @access private
   * object initialization
   */
  public function __construct($input)
  {
    /* check if input is empty and if are syntax error (invalid bounds syntax) */
    if (!empty($input) && !$this->is_syntax_error($input)) {
      $this->set_points($input);
    } else {
      throw new Exception('Interval Syntax Error!');
    }
  }

  /* check if data type are the same */
  /* private function valid_type($p1, $p2)
  {
  return gettype($p1) === gettype($p2);
  }
  */
  /**
   * @param mixed $input
   * @access private
   * check if are error in bound's syntax, accept only [ and ]
   */
  private function is_syntax_error($input)
  {
    // ! missing internal control characters

    return !(in_array($input[0], VALID_BOUNDS) && in_array(substr($input, -1), VALID_BOUNDS));
  }

  /* return interval to string */
  public function __toString()
  {
    $b1 = $this->min_bound ? '[' : ']';
    $b2 = $this->max_bound ? ']' : '[';

    return $b1 . $this->min . ',' . $this->max . $b2;
  }


  /**
   * @param mixed $input
   * @access public
   * set points to interval + input string 
   */
  public function set_points($input)
  {
    $trimmed_chunks = array_map(fn($arr) => trim($arr), explode(',', $input));

    $this->set_bounds($trimmed_chunks[0][0], substr($trimmed_chunks[1], -1));
    $p1 = substr($trimmed_chunks[0], 1);
    $p2 = substr($trimmed_chunks[1], 0, -1);
    /* se vi è lo stesso valore con notazione di inclusione differente genera errore */
    if ($p1 == $p2 && $trimmed_chunks[0][0] === substr($trimmed_chunks[1], -1)) {
      throw new Exception('Same value with different bounds !');
    }

    if ($p1 <= $p2) {
      $this->min = $p1;
      $this->max = $p2;
    } else {
      $this->max = $p1;
      $this->min = $p2;
    }

    // var_dump($this);
  }

  /**
   * @param string 
   * @access private
   * convert bound notation to boolean
   */
  private function set_bounds($b1, $b2)
  {
    $b1 == '[' ? $this->min_bound = true : $this->min_bound = false;
    $b2 == ']' ? $this->max_bound = true : $this->max_bound = false;
  }

  /**
   * @param Interval $interval
   * @access private
   * convert boolean to bound notation
   */
  private function convert_bounds($interval)
  {
    $out = array();

    $interval->min_bound ? $out[0] = '[' : $out[0] = ']';
    $interval->max_bound ? $out[1] = ']' : $out[1] = '[';

    return $out;
  }

  /**
   * @param Interval $first, $second
   * @access private
   * return a sorted array of intervals 
   */
  private function order_in_array($first, $second)
  {
    $arr = array();
    if (($first->min < $second->min) or ($first->min <= $second->min && $first->max > $second->max)) {
      $arr['first'] = $first;
      $arr['second'] = $second;
    } else {
      $arr['first'] = $second;
      $arr['second'] = $first;
    }
    return $arr;
  }

  // ANALYSES 
  /* 
  
  Possibili casistiche:
  1. Intervallo contenuto nell'altro intervallo
  (es. [1,4] - [2,3]) -> contenuto
  (es. [1,4] - ]1,4[) -> prendere estremi positivi (se ci sono)
  - l'intervallo può essere contenuto, eccetto gli estremi
  - contenuto con uno dei due estremi coincidenti
  - strettamente contenuto (entrambi gli estremi inclusi)
  2. Intervalli parzialmente coincidenti
  (es. [1,4] - [3,5])
  (es. [1,4] - ]4,5]) -> bound contenuto
  - l'intervallo può coincidere parzialmente
  - coincide solo per un estremo -> verificare inclusione dell'estremo
  
  3. Unione tra intervalli discontiui
  (es. [1,4] - [5,7])
  (es. [1,4[ - ]4,7])  
  */

  /**
   * @param mixed $input
   * @access public
   * Union of two intervals
   */
  public function union($input)
  {
    $second = new Interval($input);
    $arr = $this->order_in_array($this, $second);

    //var_dump($arr);

    // CASE : intervallo contenuto
    if ($arr['first']->min <= $arr['second']->min and $arr['first']->max >= $arr['second']->max) {
      // se gli estremi coincidono (almeno uno)
      if ($arr['first']->min == $arr['second']->min or $arr['first']->max == $arr['second']->max) {
        $b1 = ($arr['first']->min_bound or $arr['second']->min_bound) ? '[' : ']';
        $b2 = ($arr['first']->max_bound or $arr['second']->max_bound) ? ']' : '[';
        return $this->__toString() . ' union ' . $input . ' => ' . $b1 . $arr['first']->min . ',' . $arr['first']->max . $b2;
      }
      $bounds = $this->convert_bounds($arr['first']);
      return $this->__toString() . ' union ' . $input . ' => ' . $bounds[0] . $arr['first']->min . ',' . $arr['first']->max . $bounds[1];
    }

    // CASE : intervalli sono parzialmente coincidenti 
    elseif ($arr['first']->max >= $arr['second']->min) {
      $first_b = $this->convert_bounds($arr['first']);
      $second_b = $this->convert_bounds($arr['second']);

      /* se un estremo coincide verificare l'inclusione */
      if ($arr['first']->max == $arr['second']->min) {
        if ($arr['first']->max_bound or $arr['second']->min_bound) {
          return $this->__toString() . ' union ' . $input . ' => ' . $first_b[0] . $arr['first']->min . ',' . $arr['second']->max . $second_b[1];
        } else
          return $this->__toString() . ' union ' . $input . ' => ' . null;
      }
      return $this->__toString() . ' union ' . $input . ' => ' . $first_b[0] . $arr['first']->min . ',' . $arr['second']->max . $second_b[1];
    }
    // CASE : unione tra intervalli discontinui
    return $this->__toString() . ' union ' . $input . ' => ' . $this->__toString() . ' U ' . $input;
  }

  // ANALYSES
  /*
  1. Intervallo contenuto 
  es. [1,7] - [3,5]
  es. [1,7] - [1,3] 
  2. Intervallo contiguo [1,7] - [4,12]
  Possibili casistiche:
  1. Intervallo contenuto nell'altro 
  (es. [1,7] - [3,5])
  - parzialmente contenuto
  - strettamente contenuto
  2. Intervallo coincidente
  (es. [1,7] - [4,12] -> [4,7])
  (es. [1,7] [7,9] -> 7)
  - parzialmente coincidente
  - strettamente coincidente
  */

  /**
   * @param string $input -> intervallo di confronto
   * @access public
   * intersezione tra due intervalli
   */
  public function intersection($input)
  {
    $second = new Interval($input);
    $arr = $this->order_in_array($this, $second);
    /* var_dump($this);
    var_dump($arr); */

    /* contenuto */
    if ($arr['first']->min <= $arr['second']->min && $arr['first']->max >= $arr['second']->max) {
      /* strettamente contenuto */
      if ($arr['first']->min == $arr['second']->min && $arr['first']->max == $arr['second']->max) {
        $b1 = ($arr['first']->min_bound and $arr['second']->min_bound) ? '[' : ']';
        $b2 = ($arr['first']->max_bound and $arr['second']->max_bound) ? ']' : '[';
        return $this->__toString() . ' intersect ' . $input . ' => ' . $b1 . $arr['first']->min . ',' . $arr['first']->max . $b2;
      }

      /* controllo val > tra i due estremi minori degli intervalli */
      $arr['first']->min >= $arr['second']->min ? $min = $arr['first'] : $min = $arr['second'];
      $min_b = $min->min_bound;
      /* aggiustamento se coincide */
      if ($arr['first']->min == $arr['second']->min) {
        $min_b = $arr['first']->min_bound && $arr['second']->min_bound;
      }

      /* controllo val > tra i due estremi maggiori degli intervalli */
      $arr['first']->max >= $arr['second']->max ? $max = $arr['second'] : $max = $arr['first'];
      $max_b = $max->max_bound;
      /* aggiustamento se coincide */
      if ($arr['first']->max == $arr['second']->max) {
        $max_b = $arr['first']->max_bound && $arr['second']->max_bound;
      }

      $b1 = $min_b ? '[' : ']';
      $b2 = $max_b ? ']' : '[';

      return $this->__toString() . ' intersect ' . $input . ' => ' . $b1 . $min->min . ',' . $max->max . $b2;
    }

    /* se intervalli coincidenti */
    elseif ($arr['first']->max >= $arr['second']->min) {
      /* se coincidenti per lo stesso estremo verificare l'inclusione */
      if ($arr['first']->max == $arr['second']->min) {
        if ($arr['first']->max_bound && $arr['second']->min_bound) {
          // ritorna l'unico valore che coincide nell'intervallo
          return $this->__toString() . ' intersect ' . $input . ' => ' . $arr['first']->max;
        }
        /* intersection null */
        return $this->__toString() . ' intersect ' . $input . ' => null';
      }

      $min = $arr['second'];
      $max = $arr['first'];

      $b1 = $min->min_bound ? '[' : ']';
      $b2 = $max->max_bound ? ']' : '[';

      return $this->__toString() . ' intersect ' . $input . ' => ' . $b1 . $min->min . ',' . $max->max . $b2;
    }
    /* intersection null */
    return $this->__toString() . ' intersect ' . $input . ' => null';

  }
  public function difference($input)
  {
    //return difference
  }
}

$my_inter = new Interval('[1,7]');

var_dump($my_inter->union('[4,12]'));
var_dump($my_inter->intersection('[4,12]'));