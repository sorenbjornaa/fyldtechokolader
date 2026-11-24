export type ShellType = {
  slug: string;
  name: string;
};

export type ChocolateImage = {
  id: number;
  src: string;
  alt: string;
  width: number;
  height: number;
};

export type ChocolateRating = {
  average: number | null;
  count: number;
};

export type Chocolate = {
  id: number;
  slug: string;
  title: string;
  description: string;
  productionYear: number | null;
  productionDate: string | null;
  shellType: ShellType | null;
  badge: string | null;
  recipeHtml: string;
  image: ChocolateImage | null;
  rating: ChocolateRating;
  seo: {
    title: string;
    description: string;
    image: string | null;
  };
  modifiedAt: string;
};

export type ChocolateSummary = Pick<
  Chocolate,
  | "id"
  | "slug"
  | "title"
  | "description"
  | "productionYear"
  | "productionDate"
  | "shellType"
  | "badge"
  | "image"
  | "rating"
>;
