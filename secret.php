<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
set_time_limit(0);

/**
 * This is class contains logic to get secret phrase from anagram
 *
 * @author Amit Pandey
 */
class Secret {

    /**
     * MD5 Hash of the secrete phrase
     */
    const HASH = '87bb2bda651995d346c05b5049b4d78b';

    /**
     * @var string $_hint Hint to get the secret phrase
     */
    private $_hint;

    /**
     * @var string $_cleanHint hint string without space
     */
    private $_cleanHint;

    /**
     * @var string $_cleanHintKeyMap key map of the hint string without space
     */
    private $_cleanHintKeyMap;

    /**
     * @var string $_cleanHintLength length of the hint string without space
     */
    private $_cleanHintLength;

    /**
     * @var string $_totalSpaceHint total number of space in the hint string
     */
    private $_totalSpaceHint;

    /**
     * @var string $_totalSpaceHint total number of space in the hint string
     */
    private $_combinationCount;

    /**
     * @var string $_totalSpaceHint total number of space in the hint string
     */
    private $_secret = '';

    /**
     * Constructor
     * 
     * @param string $hint
     */
    public function __construct(string $hint) {
        $this->_hint = $hint;
        $this->_cleanHint = str_replace(' ', '', $this->_hint);
        $this->_cleanHintLength = strlen($this->_cleanHint);
        $this->_totalSpaceHint = substr_count($this->_hint, ' ');
        $this->_cleanHintKeyMap = count_chars($this->_cleanHint, 1);
        $this->init();
    }
    
    /**
     * Initialise the class
     * 
     * @return void
     */
    public function init() {
        
        // Possible words of secret from dictionary
        $dictWords = $this->_getWordFromDictionary();

        // Arrange the dictonary words length wise
        $dictWordsByLen = [];
        $dictWordsCountByLen = [];

        foreach ($dictWords as $word) {
            if (!is_array($word) && !isset($dictWordsByLen[strlen($word)])) {
                $dictWordsByLen[strlen($word)] = [];
                $dictWordsCountByLen[strlen($word)] = 0;
            }

            $dictWordsByLen[strlen($word)][] = $word;
            $dictWordsCountByLen[strlen($word)] += 1;
        }

        $lengthOfDict = array_keys($dictWordsByLen);
        
        // Array of possible cross joins for secret
        $possibleCrossJoins = [];
        
        // Based on available space we can say that max possible depth of the secret should be total space + 1
        $possibleDepthLevel = $this->_totalSpaceHint + 1;

        // Reduce the subset for dictionary length for possible cross joins
        $this->_subSetReduce($lengthOfDict, $this->_cleanHintLength, $possibleCrossJoins, $possibleDepthLevel);

        // Do sorting to possible cross joins to get the lowest combination first.
        $possibleCrossJoins = $this->_getSortedCrossJoins($possibleCrossJoins, $dictWordsCountByLen, $possibleDepthLevel);
        $this->_findAnagram($possibleCrossJoins, $dictWordsByLen);
    }

    /**
     * Return the secret phrase
     * 
     * @return string
     */
    public function getSecret() {
        return $this->_secret;
    }

    /**
     * Finding anagrams based cross joins from dictionary words
     * 
     * @param array $possibleCrossJoins
     * @param array $dictWordsByLen
     * @return void
     */
    protected function _findAnagram($possibleCrossJoins, $dictWordsByLen) {
        foreach ($possibleCrossJoins as $possibleCrossJoin) {
            $anagrams = [];

            foreach ($possibleCrossJoin as $crossJoinValue) {
                $anagrams[] = $dictWordsByLen[$crossJoinValue];
            }
            
            if ($this->_secret) {
                return;
            }
            
            $this->_analyseCombination($anagrams);
        }
    }

    /**
     * Analyse the anagram combinations to find the secret
     * 
     * @param array $anagrams
     * @param int $current
     * @param array $temp
     * @return void
     */
    protected function _analyseCombination($anagrams, $current = 0, $temp = []) {
        if (empty($temp)) {
            $this->_combinationCount = count($anagrams);
        }

        if (count($temp) == $this->_combinationCount) {
            if ($this->_cleanHintKeyMap == count_chars(implode('', $temp), 1)) {
                // Rearrange the anagram words in all possible order
                $possibleCombination = $this->_getUniqueCombinations($temp);
                foreach ($possibleCombination as $possibleSecret) {
                    if (self::HASH == hash('md5', $possibleSecret)) {
                        $this->_secret = $possibleSecret;
                        return;
                    }
                }
            }
        }

        if (!isset($anagrams[$current]) || $current >= $this->_combinationCount) {
            return;
        }

        $count = count($anagrams[$current]);
        for ($i = 0; $i < $count; $i++) {
            $temp2 = $temp;
            $temp2[] = $anagrams[$current][$i];
            $this->_analyseCombination($anagrams, $current + 1, $temp2);
        }
    }

    /**
     * Return unique possible anagram combinations
     * 
     * @param array $combination
     * @param array $temp
     * @return array|null
     */
    protected function _getUniqueCombinations($combination, $temp = []) {
        if (empty($temp)) {
            $this->_combinationCount = count($combination);
            $result = [];
        }
        
        if (count($temp) == $this->_combinationCount) {
            $string = implode(" ", $temp);
            return [$string];
        } elseif (count($temp) >= $this->_combinationCount) {
            return null;
        }
        
        $length = count($combination);
        $matches = [];
        for ($i = 0; $i < $length; $i++) {
            $newTemp = $temp;
            $newTemp[] = $combination[$i];
            $newCombination = $combination;
            unset($newCombination[$i]);
            $result = $this->_getUniqueCombinations(array_values($newCombination), $newTemp);
            if ($result) {
                $matches = array_merge($matches, $result);
            }
        }

        return array_unique($matches);
    }

    /**
     * Return the sorted possible cross joins
     * 
     * @param array $cossJoins
     * @param int $counts
     * @param int $depth
     * @return array
     */
    protected function _getSortedCrossJoins($cossJoins, $counts, $depth) {
        usort($cossJoins, function($first, $next) use($counts) {
            $firstCombination = array_reduce($first, function($product, $item) use($counts) {
                $product *= $counts[$item];
                return $product;
            }, 1);

            $nextCombination = array_reduce($next, function($product, $item) use($counts) {
                $product *= $counts[$item];
                return $product;
            }, 1);

            return $firstCombination - $nextCombination;
        });

        return array_values(array_filter($cossJoins, function($item) use($depth) {
                    return count($item) == $depth;
                }));
    }

    /**
     * Reduce the subset of dictionary length to get max possible cross joins for secret
     * 
     * @param array $dictLength
     * @param int $hintLength
     * @param array $combination
     * @param int $depth
     * @param array $temp
     * @return void
     */
    protected function _subSetReduce($dictLength, $hintLength, &$combination, $depth, $temp = []) {
        if (count($temp) > $depth) {
            return null;
        }
        
        if ($hintLength == 0) {
            $combination[] = $temp;
            return null;
        }
        
        if ($hintLength <= 0) {
            return null;
        }
        
        $count = count($dictLength);
        for ($i = 0; $i < $count; $i++) {
            if ($hintLength < $dictLength[$i]) {
                continue;
            }
            $temp2 = $temp;
            $temp2[] = $dictLength[$i];
            $this->_subSetReduce(array_slice($dictLength, $i), $hintLength - $dictLength[$i], $combination, $depth, $temp2);
        }
    }

    /**
     * Return possible words used in secret phrase based on hint from the dictionary
     * 
     * @return array
     */
    protected function _getWordFromDictionary() {
        // Get all the different characters used in the hint
        $pattern = count_chars($this->_cleanHint, 3);

        // Content of the dictionary
        $dictionary = file_get_contents('./dict');

        // Match the available words in the dictionary based on pattern of the hint
        preg_match_all("/^([$pattern]+)$/im", $dictionary, $possibleWords);

        return $possibleWords[0]; // All possible words from dictionary
    }

}

/* @var $secret Secret */
$secret = new Secret('wild inovation suly');
echo $secret->getSecret();