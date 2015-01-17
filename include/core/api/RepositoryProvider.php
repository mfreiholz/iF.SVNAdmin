<?php
abstract class RepositoryProvider extends Provider {

  /**
   * @param int $offset
   * @param int $num
   * @return array
   */
  public abstract function getRepositories($offset, $num);

  /**
   * @param string $id
   * @return Repository
   */
  public abstract function findRepository($id);

  /**
   * @param string $name
   * @param array $options
   * @return Repository
   */
  public abstract function create($name, $options = array());

  /**
   * @param string $id
   * @return bool
   */
  public abstract function delete($id);

  /**
   * @param $id
   * @return SvnAuthzFile
   */
  public abstract function getSvnAuthz($id);

  /**
   * Gets an array with undefined variable information about the repository.
   * @param $id
   * @return array
   */
  public abstract function getInfo($id);

}