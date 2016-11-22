<?php

class Brand extends AbstractRenderable
{
    const TABLE_NAME = 'maerke';

    // Backed by DB
    /**
     * The external link for this brand
     */
    private $link;

    /**
     * The path for the brand icon
     */
    private $iconPath;

    /**
     * Construct the entity
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setTitle($data['title'])
            ->setLink($data['link'])
            ->setIconPath($data['icon_path']);
    }

    /**
     * Map data from DB table to entity
     *
     * @param array The data from the database
     *
     * @return array
     */
    public static function mapFromDB(array $data): array
    {
        return [
            'id'        => $data['id'],
            'title'     => $data['navn'],
            'link'      => $data['link'],
            'icon_path' => $data['ico'],
        ];
    }

    // Getters and setters
    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get the external link for this brand
     *
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    public function setIconPath(string $iconPath): self
    {
        $this->iconPath = $iconPath;

        return $this;
    }

    /**
     * Get the file that is used as an icon
     *
     * @return ?\File
     */
    public function getIcon()
    {
        if (!$this->iconPath) {
            return null;
        }
        return File::getByPath($this->iconPath);
    }

    // General methodes
    /**
     * Get the url slug
     *
     * @return string
     */
    public function getSlug(): string
    {
        return 'mærke' . $this->getId() . '-' . clearFileName($this->getTitle()) . '/';
    }

    /**
     * Get all pages under this brand
     *
     * @param string $order How to order the pages
     *
     * @return array
     */
    public function getPages(string $order = 'navn'): array
    {
        return ORM::getByQuery(
            Page::class,
            "
            SELECT sider.*
            FROM sider
            WHERE maerke = " . $this->getId() . "
            ORDER BY sider.`" . db()->esc($order) . "` ASC
            "
        );
    }

    // ORM related functions
    /**
     * Save entity to database
     */
    public function save()
    {
        if ($this->id === null) {
            db()->query(
                "
                INSERT INTO `" . self::TABLE_NAME . "` (
                    `navn`,
                    `link`,
                    `icon`
                ) VALUES (
                    '" . db()->esc($this->title) . "',
                    '" . db()->esc($this->link) . "',
                    '" . db()->esc($this->getIcon() ? $this->getIcon()->getPath() : '') . "'
                )"
            );
            $this->setId(db()->insert_id);
        } else {
            db()->query(
                "
                UPDATE `" . self::TABLE_NAME ."` SET
                    `navn` = '" . db()->esc($this->title) . "',
                    `email` = '" . db()->esc($this->email) . "',
                    `icon` = '" . db()->esc($this->getIcon() ? $this->getIcon()->getPath() : '') . "'
                WHERE `id` = " . $this->id
            );
        }
        Render::addLoadedTable(self::TABLE_NAME);
    }
}
